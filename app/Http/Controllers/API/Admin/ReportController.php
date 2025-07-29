<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
    * Fetch all monthly and yearly reports.
    */
    public function allReports()
    {
        try {
            // Fetch monthly reports and add a 'type' attribute
            $monthly = MonthlyReport::with('year', 'month')->get()->map(function ($report) {
                $report->type = 'monthly';
                return $report;
            });

            // Fetch yearly reports and add a 'type' attribute
            $yearly = YearlyReport::with('year')->get()->map(function ($report) {
                $report->type = 'yearly';
                return $report;
            });

            // Merge the two collections
            $allReports = $monthly->merge($yearly)->sortByDesc('created_at')->values();

            return response()->json([
                'success' => true,
                'data' => $allReports,
                'message' => 'All reports fetched successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reports.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
