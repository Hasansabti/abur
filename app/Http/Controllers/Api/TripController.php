<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\AuditLog;


class TripController extends Controller
{

    private function getAgencyId()
    {
        if (session()->has('agency_id')) {
            return session('agency_id');
        }

        if (auth('agency')->check()) {
            return auth('agency')->id();
        }

        if (auth('employee')->check()) {
            return auth('employee')->user()->agency_id;
        }

        return null;
    }
    private function getOperatorName(): string
    {
        if (auth('employee')->check()) {
            return auth('employee')->user()->name . ' (موظف)';
        }

        if (auth('agency')->check()) {
            return auth('agency')->user()->name . ' (مالك الوكالة)';
        }

        if (session()->has('employee_name')) {
            return session('employee_name') . ' (موظف)';
        }

        if (session()->has('agency_name')) {
            return session('agency_name');
        }

        return 'نظام الوكالة الآلي';
    }

    

    public function destroy(Trip $trip): JsonResponse
    {
        $trip->delete();

        return response()->json([
            'message' => 'Trip deleted successfully.',
        ]);
    }
}
