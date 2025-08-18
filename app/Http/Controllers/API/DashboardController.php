<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\News;
use App\Models\MonthlyReport;
use App\Models\YearlyReport;
use App\Models\ServiceRequest;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats()
    {
        try {
        $stats = [
            'total_users' => User::count(),
            'total_news' => News::count(),
            'published_news' => News::whereNotNull('published_at')->count(),
            'total_service_requests' => ServiceRequest::count(),
            'completed_service_requests' => ServiceRequest::whereHas('status', function($query) {
                $query->where('name', 'Completed');
            })->count(),
            'pending_service_requests' => ServiceRequest::whereHas('status', function($query) {
                $query->where('name', 'Pending');
            })->count(),
            'inProgress_service_requests' => ServiceRequest::whereHas('status', function($query) {
                $query->where('name', 'In Progress');
            })->count(),
            'rejected_service_requests' => ServiceRequest::whereHas('status', function($query) {
                $query->where('name', 'Rejected');
            })->count(),
            'total_monthly_reports' => MonthlyReport::count(),
            'published_monthly_reports' => MonthlyReport::where('status', 'published')->count(),
            'total_yearly_reports' => YearlyReport::count(),
            'published_yearly_reports' => YearlyReport::where('status', 'published')->count(),
            'recent_service_requests' => ServiceRequest::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'recent_news' => News::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
        ];            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer growth data by year
     */
    public function customerGrowthData($year = null)
    {
        try {
            $year = $year ?: date('Y');
            
            // Get service requests growth by month for the specified year
            $growth_data = ServiceRequest::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('MONTHNAME(created_at) as month_name')
            )
            ->whereYear('created_at', $year)
            ->where('status_id', 11) // Assuming 11 is the ID for "Completed" status
            ->groupBy(DB::raw('MONTH(created_at)'), DB::raw('MONTHNAME(created_at)'))
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get();

            // Fill in missing months with 0 counts
            $months = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];

            $formatted_data = [];
            foreach ($months as $month_num => $month_name) {
                $existing = $growth_data->firstWhere('month', $month_num);
                $formatted_data[] = [
                    'month' => $month_num,
                    'month_name' => $month_name,
                    'count' => $existing ? $existing->count : 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'monthly_data' => $formatted_data,
                    'total_for_year' => array_sum(array_column($formatted_data, 'count'))
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer growth data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent news
     */
    public function recentNews(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);
            
            $recent_news = News::with('category')
                ->whereNotNull('published_at')
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($news) {
                    return [
                        'id' => $news->id,
                        'title' => $news->title,
                        'slug' => $news->slug,
                        'content' => $news->content,
                        'excerpt' => Str::limit(strip_tags($news->content), 150),
                        'image' => $news->image,
                        'published_at' => $news->published_at,
                        'featured' => $news->featured,
                        'category' => $news->category ? [
                            'id' => $news->category->id,
                            'name' => $news->category->name,
                            'slug' => $news->category->slug
                        ] : null,
                        'created_at' => $news->created_at,
                        'updated_at' => $news->updated_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recent_news
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent news',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent reports (both monthly and yearly)
     */
    public function recentReports(Request $request)
    {
        try {
            $limit = $request->query('limit', 3);
            
            // Get recent monthly reports
            $monthly_reports = MonthlyReport::select('id', 'title', 'month_id', 'year_id', 'published_at', 'created_at', 'updated_at')
                ->with(['month', 'year'])
                ->whereNotNull('published_at')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'title' => $report->title,
                        'type' => 'monthly',
                        'period' => $report->month->name . ' ' . $report->year->year,
                        'month' => $report->month ? $report->month->name : null,
                        'year' => $report->year ? $report->year->year : null,
                        'is_published' => $report->published_at !== null,
                        'created_at' => $report->created_at,
                        'updated_at' => $report->updated_at
                    ];
                });

            // Get recent yearly reports
            $yearly_reports = YearlyReport::select('id', 'title', 'year_id', 'published_at', 'created_at', 'updated_at')
                ->with('year')
                ->whereNotNull('published_at')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'title' => $report->title,
                        'type' => 'yearly',
                        'period' => $report->year->year,
                        'year' => $report->year ? $report->year->year : null,
                        'is_published' => $report->published_at !== null,
                        'created_at' => $report->created_at,
                        'updated_at' => $report->updated_at
                    ];
                });

            // Combine and sort by creation date
            $all_reports = $monthly_reports->concat($yearly_reports)
                ->sortByDesc('created_at')
                ->take($limit)
                ->values();

            return response()->json([
                'success' => true,
                'data' => $all_reports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
