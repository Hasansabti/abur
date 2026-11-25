<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyRating;
use Illuminate\Http\Request;

class RatingAgencyController extends Controller
{
    public function create(Agency $agency)
    {
        return view('client.ratings.public_rate', compact('agency'));
    }

    public function store(Request $request, Agency $agency)
    {
        $validated = $request->validate([
            'trip_id'             => 'nullable|exists:trips,id', // 🟢 التأكد من وجود الرحلة في قاعدة البيانات
            'transport_rating'    => 'required|integer|min:1|max:5',
            'staff_rating'        => 'required|integer|min:1|max:5',
            'organization_rating' => 'required|integer|min:1|max:5',
            'price_value_rating'  => 'required|integer|min:1|max:5',
            'services_rating'     => 'nullable|integer|min:1|max:5',
            'comment'             => 'nullable|string|max:1000',
        ]);

        AgencyRating::create([
            'agency_id'           => $agency->id,
            'user_id'             => auth()->id(),
            'trip_id'             => $validated['trip_id'] ?? null, // 🟢 قراءة القيمة بعد الفحص
            'transport_rating'    => $validated['transport_rating'],
            'staff_rating'        => $validated['staff_rating'],
            'organization_rating' => $validated['organization_rating'],
            'price_value_rating'  => $validated['price_value_rating'],
            'services_rating'     => $validated['services_rating'] ?? 0,
            'comment'             => $validated['comment'] ?? null,
        ]);

        return back()->with('success', 'شكراً لك! تم إرسال تقييمك بنجاح ويسهم في تحسين خدماتنا.');
    }
}
