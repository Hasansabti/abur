<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Agency::query()->latest()->paginate(25));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:agencies,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $agency = Agency::create($data);

        return response()->json($agency, 201);
    }
}
