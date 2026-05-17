<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Trip::query()->with('agency');

        if ($request->filled('transport_type')) {
            $query->where('transport_type', (string) $request->input('transport_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->integer('agency_id'));
        }

        if ($request->filled('from_city')) {
            $query->where('from_city', 'like', '%'.(string) $request->input('from_city').'%');
        }

        if ($request->filled('to_city')) {
            $query->where('to_city', 'like', '%'.(string) $request->input('to_city').'%');
        }

        $perPage = max(1, min($request->integer('per_page', 15), 100));
        $trips = $query->latest('departure_at')->paginate($perPage);

        return response()->json($trips);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agency_id' => ['required', 'exists:agencies,id'],
            'title' => ['required', 'string', 'max:255'],
            'from_city' => ['required', 'string', 'max:255'],
            'to_city' => ['required', 'string', 'max:255'],
            'transport_type' => ['required', 'in:bus,flight'],
            'trip_type' => ['required', 'in:one_way,round_trip'],
            'departure_at' => ['required', 'date'],
            'arrival_at' => ['nullable', 'date', 'after_or_equal:departure_at'],
            'total_seats' => ['required', 'integer', 'min:1'],
            'available_seats' => ['nullable', 'integer', 'min:0', 'lte:total_seats'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $data['available_seats'] = $data['available_seats'] ?? $data['total_seats'];
        $trip = Trip::create($data)->load('agency');

        return response()->json($trip, 201);
    }

    public function show(Trip $trip): JsonResponse
    {
        return response()->json($trip->load('agency'));
    }

    public function update(Request $request, Trip $trip): JsonResponse
    {
        $data = $request->validate([
            'agency_id' => ['sometimes', 'exists:agencies,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'from_city' => ['sometimes', 'string', 'max:255'],
            'to_city' => ['sometimes', 'string', 'max:255'],
            'transport_type' => ['sometimes', 'in:bus,flight'],
            'trip_type' => ['sometimes', 'in:one_way,round_trip'],
            'departure_at' => ['sometimes', 'date'],
            'arrival_at' => ['nullable', 'date'],
            'total_seats' => ['sometimes', 'integer', 'min:1'],
            'available_seats' => ['sometimes', 'integer', 'min:0'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'max:50'],
        ]);

        $trip->update($data);

        return response()->json($trip->fresh()->load('agency'));
    }

    public function destroy(Trip $trip): JsonResponse
    {
        $trip->delete();

        return response()->json([
            'message' => 'Trip deleted successfully.',
        ]);
    }
}
