<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Booking;
use App\Support\AgencyPermission;

trait ResolvesAgencyContext
{
    protected function getAgencyId()
    {
        if (auth('agency')->check()) {
            return auth('agency')->id();
        }
        if (auth('employee')->check()) {
            return auth('employee')->user()->agency_id;
        }
        if (session()->has('agency_id')) {
            return session('agency_id');
        }

        return null;
    }

    protected function getOperatorName(): string
    {
        if (auth('employee')->check()) {
            return auth('employee')->user()->name . ' (موظف)';
        }
        if (auth('agency')->check()) {
            return auth('agency')->user()->name . ' (مالك الوكالة)';
        }

        return session('employee_name') ?? session('agency_name') ?? 'نظام الوكالة الآلي';
    }

    protected function findAgencyBooking(int $id): Booking
    {
        $agencyId = $this->getAgencyId();

        return Booking::query()
            ->where(function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId)
                    ->orWhereHas('trip', fn ($t) => $t->where('agency_id', $agencyId));
            })
            ->with(['trip.agency', 'bookingSeats', 'payments', 'user', 'invoice', 'customServices'])
            ->findOrFail($id);
    }

    protected function denyUnless(string $ability): void
    {
        AgencyPermission::denyUnless($ability);
    }

    protected function jsonOk(string $message, array $data = [], int $status = 200)
    {
        return response()->json(array_merge(['success' => true, 'message' => $message], $data), $status);
    }

    protected function jsonFail(string $message, int $status = 422)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
