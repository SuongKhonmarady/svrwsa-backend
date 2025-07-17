<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Month;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MonthController extends Controller
{
    /**
     * Get all months
     */
    public function index(): JsonResponse
    {
        try {
            $months = Month::orderBy('id')->get();
            
            return response()->json([
                'success' => true,
                'data' => $months,
                'message' => 'Months retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving months: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific month with its reports
     */
    public function show($id): JsonResponse
    {
        try {
            $month = Month::with(['monthlyReports.year'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $month,
                'message' => 'Month retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving month: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get months by quarter
     */
    public function byQuarter(Request $request): JsonResponse
    {
        try {
            $quarter = $request->get('quarter', 1);
            
            $months = Month::byQuarter($quarter)
                ->orderBy('id')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $months,
                'message' => 'Months retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving months: ' . $e->getMessage()
            ], 500);
        }
    }
}
