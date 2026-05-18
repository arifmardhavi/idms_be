<?php

namespace App\Http\Controllers;

use App\Services\DashboardRkapService;
use Illuminate\Http\Request;

class DashboardRkapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, DashboardRkapService $service)
    {
        $usd = $request->get('usd');

        // 🔥 validasi usd
        if ($usd && (!is_numeric($usd) || $usd <= 0)) {
            return response()->json([
                'success' => false,
                'message' => 'USD must be a positive number'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dashboard RKAP retrieved successfully.',
            'data' => $service->getData($usd),
            'meta' => [
                'currency' => $usd ? 'USD' : 'IDR',
                'rate' => $usd ? (float) $usd : null,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
