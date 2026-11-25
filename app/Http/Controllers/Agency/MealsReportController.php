<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Concerns\ResolvesAgencyContext;
use App\Http\Controllers\Controller;
use App\Models\BookingSeat;
use App\Models\Trip;
use App\Support\PassengerSeat;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MealsReportController extends Controller
{
    use ResolvesAgencyContext;

    public function index(Request $request)
    {
        $this->denyUnless('view_meals_reports');
        $agencyId = $this->getAgencyId();
        $trips = Trip::where('agency_id', $agencyId)->latest()->get(['id', 'title']);
        $rows = $this->query($request, $agencyId);
        $summary = $this->summarize($rows);

        if ($request->get('export') === 'excel') {
            return $this->exportExcel($rows);
        }

        if ($request->get('export') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('agency.reports.meals_pdf', compact('rows', 'summary'))
                ->setPaper('a4', 'landscape');

            return $pdf->download('meals-report.pdf');
        }

        return view('agency.reports.meals', compact('trips', 'rows', 'summary'));
    }

    protected function query(Request $request, int $agencyId)
    {
        $q = BookingSeat::query()
            ->with(['booking.trip', 'room'])
            ->whereHas('booking', function ($b) use ($agencyId, $request) {
                $b->where(function ($inner) use ($agencyId) {
                    $inner->where('agency_id', $agencyId)
                        ->orWhereHas('trip', fn ($t) => $t->where('agency_id', $agencyId));
                })->whereNotIn('status', ['cancelled', 'requestC']);
            });

        if ($request->filled('trip_id')) {
            $q->where('trip_id', $request->trip_id);
        }
        if ($request->filled('booking_id')) {
            $q->where('booking_id', $request->booking_id);
        }
        if ($request->filled('passenger')) {
            $q->where('passenger_name', 'like', '%' . $request->passenger . '%');
        }
        if ($request->filled('package')) {
            $q->where('meal_options->package_name', 'like', '%' . $request->package . '%');
        }

        $seats = $q->get()->filter(fn ($s) => PassengerSeat::isActualGuest($s));

        if ($request->filled('meal')) {
            $meal = $request->meal;
            $seats = $seats->filter(function ($s) use ($meal) {
                return match ($meal) {
                    'breakfast' => (bool) $s->with_breakfast,
                    'lunch' => (bool) $s->with_lunch,
                    'dinner' => (bool) $s->with_dinner,
                    default => true,
                };
            });
        }

        return $seats->values();
    }

    protected function summarize($rows): array
    {
        $withMeals = $rows->filter(fn ($s) => $s->with_breakfast || $s->with_lunch || $s->with_dinner || !empty(($s->meal_snapshot['package_name'] ?? $s->meal_options['package_name'] ?? null)));

        return [
            'people' => $withMeals->count(),
            'breakfast' => $rows->where('with_breakfast', true)->count(),
            'lunch' => $rows->where('with_lunch', true)->count(),
            'dinner' => $rows->where('with_dinner', true)->count(),
            'packages' => $rows->groupBy(fn ($s) => $s->meal_snapshot['package_name'] ?? $s->meal_options['package_name'] ?? 'بدون باقة')
                ->map->count(),
            'total_value' => $rows->sum(function ($s) {
                return (float) (($s->meal_snapshot['package_price'] ?? $s->meal_options['package_price'] ?? 0)
                    + ($s->breakfast_price ?? 0) + ($s->lunch_price ?? 0) + ($s->dinner_price ?? 0));
            }),
        ];
    }

    protected function exportExcel($rows): StreamedResponse
    {
        $this->denyUnless('export_reports');
        $filename = 'meals-report-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['التسلسل', 'رقم الحجز', 'الراكب', 'الرحلة', 'الفندق', 'الغرفة', 'الباقة', 'إفطار', 'غداء', 'عشاء', 'السعر']);
            $i = 1;
            foreach ($rows as $s) {
                $snap = $s->meal_snapshot ?? $s->meal_options ?? [];
                fputcsv($out, [
                    $i++,
                    $s->booking_id,
                    $s->passenger_name,
                    $s->booking?->trip?->title,
                    $s->booking?->trip?->hotel_name,
                    $s->room?->room_number,
                    $snap['package_name'] ?? '',
                    $s->with_breakfast ? 'نعم' : 'لا',
                    $s->with_lunch ? 'نعم' : 'لا',
                    $s->with_dinner ? 'نعم' : 'لا',
                    $snap['package_price'] ?? 0,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
