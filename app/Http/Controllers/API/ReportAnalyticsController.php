<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Year;
use App\Models\Month;
use App\Models\MonthlyReport;
use App\Models\YearlyReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportAnalyticsController extends Controller
{
    /**
     * Get comprehensive analytics dashboard data
     */
    public function dashboard(): JsonResponse
    {
        try {
            $data = [
                'overview' => $this->getOverview(),
                'yearly_stats' => $this->getYearlyStats(),
                'monthly_completion' => $this->getMonthlyCompletion(),
                'missing_reports' => $this->getMissingReports(),
                'recent_activity' => $this->getRecentActivity()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Analytics data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overview statistics
     */
    public function overview(): JsonResponse
    {
        try {
            $data = $this->getOverview();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Overview statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving overview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get missing reports
     */
    public function missingReports(): JsonResponse
    {
        try {
            $data = $this->getMissingReports();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Missing reports retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving missing reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get completion rates by year
     */
    public function completionRates(): JsonResponse
    {
        try {
            $data = $this->getYearlyStats();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Completion rates retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving completion rates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly completion statistics
     */
    public function monthlyCompletion(): JsonResponse
    {
        try {
            $data = $this->getMonthlyCompletion();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Monthly completion statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly completion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reports by status
     */
    public function reportsByStatus(): JsonResponse
    {
        try {
            $monthlyByStatus = MonthlyReport::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();
                
            $yearlyByStatus = YearlyReport::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();
            
            $data = [
                'monthly' => $monthlyByStatus,
                'yearly' => $yearlyByStatus
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Reports by status retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving reports by status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Private method to get overview statistics
     */
    private function getOverview(): array
    {
        $totalMonthlyReports = MonthlyReport::count();
        $totalYearlyReports = YearlyReport::count();
        $totalReports = $totalMonthlyReports + $totalYearlyReports;
        
        $publishedMonthly = MonthlyReport::where('status', 'published')->count();
        $publishedYearly = YearlyReport::where('status', 'published')->count();
        
        $draftMonthly = MonthlyReport::where('status', 'draft')->count();
        $draftYearly = YearlyReport::where('status', 'draft')->count();
        
        $currentYear = date('Y');
        $currentYearMonthly = MonthlyReport::byYear($currentYear)->count();
        $currentYearYearly = YearlyReport::byYear($currentYear)->count();
        
        return [
            'total_reports' => $totalReports,
            'monthly_reports' => $totalMonthlyReports,
            'yearly_reports' => $totalYearlyReports,
            'published_reports' => $publishedMonthly + $publishedYearly,
            'draft_reports' => $draftMonthly + $draftYearly,
            'current_year_monthly' => $currentYearMonthly,
            'current_year_yearly' => $currentYearYearly,
            'current_year_completion' => round(($currentYearMonthly / 12) * 100, 1)
        ];
    }

    /**
     * Private method to get yearly statistics
     */
    private function getYearlyStats(): array
    {
        $years = Year::with(['monthlyReports', 'yearlyReport'])
            ->whereBetween('year_value', [2020, date('Y')])
            ->orderBy('year_value', 'desc')
            ->get();
        
        $stats = [];
        
        foreach ($years as $year) {
            $monthlyCount = $year->monthlyReports->count();
            $yearlyCount = $year->yearlyReport->count();
            $totalReports = $monthlyCount + $yearlyCount;
            
            $stats[] = [
                'year' => $year->year_value,
                'monthly_reports' => $monthlyCount,
                'yearly_reports' => $yearlyCount,
                'total_reports' => $totalReports,
                'monthly_completion_rate' => round(($monthlyCount / 12) * 100, 1),
                'missing_monthly' => 12 - $monthlyCount,
                'yearly_status' => $yearlyCount > 0 ? 'Complete' : 'Missing'
            ];
        }
        
        return $stats;
    }

    /**
     * Private method to get monthly completion statistics
     */
    private function getMonthlyCompletion(): array
    {
        $months = Month::with(['monthlyReports.year'])->get();
        
        $completion = [];
        
        foreach ($months as $month) {
            $totalReports = $month->monthlyReports->count();
            $publishedReports = $month->monthlyReports->where('status', 'published')->count();
            
            $completion[] = [
                'month' => $month->month,
                'month_number' => $month->id,
                'total_reports' => $totalReports,
                'published_reports' => $publishedReports,
                'draft_reports' => $totalReports - $publishedReports
            ];
        }
        
        return $completion;
    }

    /**
     * Private method to get missing reports
     */
    private function getMissingReports(): array
    {
        $currentYear = date('Y');
        $years = Year::whereBetween('year_value', [2020, $currentYear])->get();
        
        $missing = [
            'monthly' => [],
            'yearly' => []
        ];
        
        foreach ($years as $year) {
            // Check missing monthly reports
            $existingMonthlyReports = MonthlyReport::where('year_id', $year->id)
                ->pluck('month_id')
                ->toArray();
                
            $missingMonths = Month::whereNotIn('id', $existingMonthlyReports)->get();
            
            foreach ($missingMonths as $month) {
                $missing['monthly'][] = [
                    'year' => $year->year_value,
                    'month' => $month->month,
                    'month_id' => $month->id,
                    'year_id' => $year->id,
                    'period' => "{$month->month} {$year->year_value}"
                ];
            }
            
            // Check missing yearly reports
            $yearlyReport = YearlyReport::where('year_id', $year->id)->first();
            if (!$yearlyReport) {
                $missing['yearly'][] = [
                    'year' => $year->year_value,
                    'year_id' => $year->id,
                    'period' => "Year {$year->year_value}"
                ];
            }
        }
        
        return $missing;
    }

    /**
     * Private method to get recent activity
     */
    private function getRecentActivity(): array
    {
        $recentMonthly = MonthlyReport::with(['year', 'month'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        $recentYearly = YearlyReport::with(['year'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Combine and sort by created_at
        $allRecent = collect()
            ->concat($recentMonthly->map(function ($report) {
                return [
                    'id' => $report->id,
                    'title' => $report->title,
                    'type' => 'monthly',
                    'period' => $report->report_period,
                    'status' => $report->status,
                    'created_at' => $report->created_at,
                    'created_by' => $report->created_by
                ];
            }))
            ->concat($recentYearly->map(function ($report) {
                return [
                    'id' => $report->id,
                    'title' => $report->title,
                    'type' => 'yearly',
                    'period' => $report->report_period,
                    'status' => $report->status,
                    'created_at' => $report->created_at,
                    'created_by' => $report->created_by
                ];
            }))
            ->sortByDesc('created_at')
            ->take(10)
            ->values();
        
        return $allRecent->toArray();
    }
}
