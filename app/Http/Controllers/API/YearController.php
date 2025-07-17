<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Year;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class YearController extends Controller
{
    /**
     * Get all years
     */
    public function index(): JsonResponse
    {
        try {
            $years = Year::orderBy('year_value', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $years,
                'message' => 'Years retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving years: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific year with its reports
     */
    public function show($id): JsonResponse
    {
        try {
            $year = Year::with(['monthlyReports.month', 'yearlyReport'])
                ->findOrFail($id);
            
            // Add completion statistics
            $year->completion_percentage = $year->getCompletionPercentage();
            $year->missing_monthly_reports = $year->getMissingMonthlyReports();
            
            return response()->json([
                'success' => true,
                'data' => $year,
                'message' => 'Year retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving year: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get years within a specific range
     */
    public function range(Request $request): JsonResponse
    {
        try {
            $startYear = $request->get('start_year', 2014);
            $endYear = $request->get('end_year', date('Y'));
            
            $years = Year::withinRange($startYear, $endYear)
                ->orderBy('year_value', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $years,
                'message' => 'Years retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving years: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current year
     */
    public function current(): JsonResponse
    {
        try {
            $year = Year::current()->first();
            
            if (!$year) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current year not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $year,
                'message' => 'Current year retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving current year: ' . $e->getMessage()
            ], 500);
        }
    }
}
