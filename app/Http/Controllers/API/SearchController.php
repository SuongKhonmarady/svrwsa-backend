<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\MonthlyReport;
use App\Models\YearlyReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    /**
     * Global search across news, reports, and other content
     */
    public function globalSearch(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $limit = $request->get('limit', 10);
            
            if (empty($query) || strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Search query too short'
                ]);
            }

            // Add timing for debugging
            $startTime = microtime(true);

            $totalCount = 0;
            $results = [];

            // OPTION 1: Single optimized query combining all tables
            $combinedResults = $this->performCombinedSearch($query, $limit);
            
            if (!empty($combinedResults['news'])) {
                $results['news'] = $combinedResults['news'];
                $totalCount += count($combinedResults['news']);
            }
            
            if (!empty($combinedResults['monthly_reports'])) {
                $results['monthly_reports'] = $combinedResults['monthly_reports'];
                $totalCount += count($combinedResults['monthly_reports']);
            }
            
            if (!empty($combinedResults['yearly_reports'])) {
                $results['yearly_reports'] = $combinedResults['yearly_reports'];
                $totalCount += count($combinedResults['yearly_reports']);
            }

            $endTime = microtime(true);
            \Log::info('Search completed in: ' . round(($endTime - $startTime), 3) . ' seconds');

            return response()->json([
                'success' => true,
                'data' => $results,
                'total_count' => $totalCount,
                'query' => $query,
                'search_time' => round(($endTime - $startTime), 3) . 's',
                'message' => 'Search completed successfully'
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing search: ' . $e->getMessage()
            ], 500);
        }
    }

    // FAST METHOD: Using Eloquent with eager loading for relationships
    private function performCombinedSearch(string $query, int $limit): array
    {
        $results = [
            'news' => [],
            'monthly_reports' => [],
            'yearly_reports' => []
        ];
        
        // Search News - Enhanced multi-word search
        $news = News::select(['id', 'title', 'content', 'slug', 'created_at', 'updated_at'])
                    ->where(function($q) use ($query) {
                        // Split query into words for better matching
                        $words = array_filter(explode(' ', trim($query)));
                        
                        if (count($words) > 1) {
                            // Multi-word search: each word must be found (AND logic)
                            foreach ($words as $word) {
                                $q->where(function($subQ) use ($word) {
                                    $subQ->where('title', 'LIKE', "%{$word}%")
                                         ->orWhere('content', 'LIKE', "%{$word}%");
                                });
                            }
                        } else {
                            // Single word search: original logic
                            $q->where('title', 'LIKE', "{$query}%")  // Starts with - fastest
                              ->orWhere('title', 'LIKE', "%{$query}%")  // Contains
                              ->orWhere('content', 'LIKE', "%{$query}%");
                        }
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
        
        if ($news->count() > 0) {
            $results['news'] = $news->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'content' => $item->content ?? '',
                    'slug' => $item->slug,
                    'type' => 'news',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            })->toArray();
        }
        
        // Search Monthly Reports with relationships - Enhanced multi-word search
        $monthlyReports = MonthlyReport::with(['month', 'year'])  // Eager load relationships
                                    ->select(['id', 'title', 'description', 'month_id', 'year_id', 'created_at', 'updated_at'])
                                    ->where(function($q) use ($query) {
                                        // Split query into words for better matching
                                        $words = array_filter(explode(' ', trim($query)));
                                        
                                        if (count($words) > 1) {
                                            // Multi-word search: each word must be found (AND logic)
                                            foreach ($words as $word) {
                                                $q->where(function($subQ) use ($word) {
                                                    $subQ->where('title', 'LIKE', "%{$word}%")
                                                         ->orWhere('description', 'LIKE', "%{$word}%");
                                                });
                                            }
                                        } else {
                                            // Single word search: original logic
                                            $q->where('title', 'LIKE', "{$query}%")
                                                ->orWhere('title', 'LIKE', "%{$query}%")
                                                ->orWhere('description', 'LIKE', "%{$query}%");
                                        }
                                    })
                                    ->orderBy('created_at', 'desc')
                                    ->limit($limit)
                                    ->get();
        
        if ($monthlyReports->count() > 0) {
            $results['monthly_reports'] = $monthlyReports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'title' => $report->title,
                    'description' => $report->description ?? '',
                    'month' => $report->month ? [
                        'id' => $report->month->id,
                        'month' => $report->month->month,
                        'created_at' => $report->month->created_at,
                        'updated_at' => $report->month->updated_at,
                    ] : null,
                    'year' => $report->year ? [
                        'id' => $report->year->id,
                        'year_value' => $report->year->year_value,
                        'created_at' => $report->year->created_at,
                        'updated_at' => $report->year->updated_at,
                    ] : null,
                    'type' => 'monthly_report',
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ];
            })->toArray();
        }
        
        // Search Yearly Reports with relationships - Enhanced multi-word search
        $yearlyReports = YearlyReport::with(['year'])  // Eager load year relationship
                                    ->select(['id', 'title', 'description', 'year_id', 'created_at', 'updated_at'])
                                    ->where(function($q) use ($query) {
                                        // Split query into words for better matching
                                        $words = array_filter(explode(' ', trim($query)));
                                        
                                        if (count($words) > 1) {
                                            // Multi-word search: each word must be found (AND logic)
                                            foreach ($words as $word) {
                                                $q->where(function($subQ) use ($word) {
                                                    $subQ->where('title', 'LIKE', "%{$word}%")
                                                         ->orWhere('description', 'LIKE', "%{$word}%");
                                                });
                                            }
                                        } else {
                                            // Single word search: original logic
                                            $q->where('title', 'LIKE', "{$query}%")
                                              ->orWhere('title', 'LIKE', "%{$query}%")
                                              ->orWhere('description', 'LIKE', "%{$query}%");
                                        }
                                    })
                                    ->orderBy('created_at', 'desc')
                                    ->limit($limit)
                                    ->get();
        
        if ($yearlyReports->count() > 0) {
            $results['yearly_reports'] = $yearlyReports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'title' => $report->title,
                    'description' => $report->description ?? '',
                    'year' => $report->year ? [
                        'id' => $report->year->id,
                        'year_value' => $report->year->year_value,
                        'created_at' => $report->year->created_at,
                        'updated_at' => $report->year->updated_at,
                    ] : null,
                    'type' => 'yearly_report',
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ];
            })->toArray();
        }
        
        return $results;
    }

    // ALTERNATIVE: Ultra-fast raw SQL with JOINs for relationships
    private function performOptimizedRawSearch(string $query, int $limit): array
    {
        $escapedQuery = '%' . addslashes($query) . '%';
        
        // News search (no relationships needed)
        $newsResults = \DB::table('news')
            ->select(['id', 'title', 'content', 'slug', 'created_at', 'updated_at'])
            ->where(function($q) use ($escapedQuery) {
                $q->where('title', 'LIKE', $escapedQuery)
                ->orWhere('content', 'LIKE', $escapedQuery);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'content' => $item->content ?? '',
                    'slug' => $item->slug,
                    'type' => 'news',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            })->toArray();

        // Monthly reports with JOINs to get month and year data
        $monthlyResults = \DB::table('monthly_reports as mr')
            ->leftJoin('months as m', 'mr.month_id', '=', 'm.id')
            ->leftJoin('years as y', 'mr.year_id', '=', 'y.id')
            ->select([
                'mr.id', 'mr.title', 'mr.description', 'mr.created_at', 'mr.updated_at',
                'm.id as month_id', 'm.month as month_name', 'm.created_at as month_created_at', 'm.updated_at as month_updated_at',
                'y.id as year_id', 'y.year_value', 'y.created_at as year_created_at', 'y.updated_at as year_updated_at'
            ])
            ->where(function($q) use ($escapedQuery) {
                $q->where('mr.title', 'LIKE', $escapedQuery)
                ->orWhere('mr.description', 'LIKE', $escapedQuery);
            })
            ->orderBy('mr.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description ?? '',
                    'month' => $item->month_id ? [
                        'id' => $item->month_id,
                        'month' => $item->month_name,
                        'created_at' => $item->month_created_at,
                        'updated_at' => $item->month_updated_at,
                    ] : null,
                    'year' => $item->year_id ? [
                        'id' => $item->year_id,
                        'year_value' => $item->year_value,
                        'created_at' => $item->year_created_at,
                        'updated_at' => $item->year_updated_at,
                    ] : null,
                    'type' => 'monthly_report',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            })->toArray();

        // Yearly reports with JOIN to get year data
        $yearlyResults = \DB::table('yearly_reports as yr')
            ->leftJoin('years as y', 'yr.year_id', '=', 'y.id')
            ->select([
                'yr.id', 'yr.title', 'yr.description', 'yr.created_at', 'yr.updated_at',
                'y.id as year_id', 'y.year_value', 'y.created_at as year_created_at', 'y.updated_at as year_updated_at'
            ])
            ->where(function($q) use ($escapedQuery) {
                $q->where('yr.title', 'LIKE', $escapedQuery)
                ->orWhere('yr.description', 'LIKE', $escapedQuery);
            })
            ->orderBy('yr.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description ?? '',
                    'year' => $item->year_id ? [
                        'id' => $item->year_id,
                        'year_value' => $item->year_value,
                        'created_at' => $item->year_created_at,
                        'updated_at' => $item->year_updated_at,
                    ] : null,
                    'type' => 'yearly_report',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            })->toArray();

        return [
            'news' => $newsResults,
            'monthly_reports' => $monthlyResults,
            'yearly_reports' => $yearlyResults
        ];
    }

    /**
     * Search suggestions for autocomplete
     */
    public function searchSuggestions(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $limit = $request->get('limit', 8);
            
            if (empty($query) || strlen($query) < 1) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Search query too short'
                ]);
            }

            $suggestions = [];

            // Get exact title matches first (highest priority)
            $exactTitles = News::where('title', 'LIKE', "%{$query}%")
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['title', 'id', 'slug'])
                ->map(function($item) use ($query) {
                    return [
                        'text' => $item->title,
                        'type' => 'exact',
                        'id' => $item->id,
                        'slug' => $item->slug,
                        'match_type' => 'title'
                    ];
                })
                ->toArray();

            // Get word-based matches from content
            $contentMatches = News::where('content', 'LIKE', "%{$query}%")
                ->whereNotIn('id', collect($exactTitles)->pluck('id')->toArray())
                ->orderBy('created_at', 'desc')
                ->limit($limit - count($exactTitles))
                ->get(['title', 'content', 'id', 'slug'])
                ->map(function($item) use ($query) {
                    // Extract relevant phrase from content
                    $content = strip_tags($item->content);
                    $queryPos = stripos($content, $query);
                    if ($queryPos !== false) {
                        $start = max(0, $queryPos - 30);
                        $excerpt = substr($content, $start, 80);
                        $highlightedExcerpt = str_ireplace($query, "**{$query}**", $excerpt);
                        
                        return [
                            'text' => $item->title,
                            'type' => 'content',
                            'id' => $item->id,
                            'slug' => $item->slug,
                            'excerpt' => $highlightedExcerpt,
                            'match_type' => 'content'
                        ];
                    }
                    return null;
                })
                ->filter()
                ->toArray();

            // Generate smart query suggestions based on common search patterns
            $smartSuggestions = $this->generateSmartSuggestions($query, $limit);

            // Combine all suggestions with priority: exact matches > content matches > smart suggestions
            $allSuggestions = array_merge($exactTitles, $contentMatches, $smartSuggestions);
            
            // Limit to requested amount and ensure uniqueness
            $finalSuggestions = collect($allSuggestions)
                ->unique('text')
                ->take($limit)
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $finalSuggestions,
                'query' => $query,
                'message' => 'Smart suggestions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting suggestions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search within news articles
     */
    private function searchNews(string $query, int $limit): array
    {
        $news = News::where(function($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $news->map(function($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'content_preview' => $this->getContentPreview($item->content, 150),
                'image' => $item->image,
                'published_at' => $item->published_at,
                'created_at' => $item->created_at,
                'slug' => $item->slug,
                'type' => 'news',
                'route' => '/news/' . $item->slug
            ];
        })->toArray();
    }

    // Search methods for both report types
    private function searchMonthlyReports(string $query, int $limit): array
    {
        // Assuming you have a MonthlyReport model
        $monthlyReports = MonthlyReport::where('title', 'LIKE', "%{$query}%")
                                    ->orWhere('description', 'LIKE', "%{$query}%")
                                    ->limit($limit)
                                    ->get();

        return $monthlyReports->map(function ($report) {
            return [
                'id' => $report->id,
                'title' => $report->title,
                'description' => $report->description ?? '',
                'month' => $report->month ?? '',
                'year' => $report->year ?? '',
                'type' => 'monthly_report',
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
                // Add other monthly report specific fields
            ];
        })->toArray();
    }

    private function searchYearlyReports(string $query, int $limit): array
    {
        // Assuming you have a YearlyReport model
        $yearlyReports = YearlyReport::where('title', 'LIKE', "%{$query}%")
                                    ->orWhere('description', 'LIKE', "%{$query}%")
                                    ->limit($limit)
                                    ->get();

        return $yearlyReports->map(function ($report) {
            return [
                'id' => $report->id,
                'title' => $report->title,
                'description' => $report->description ?? '',
                'year' => $report->year ?? '',
                'type' => 'yearly_report',
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
                // Add other yearly report specific fields
            ];
        })->toArray();
    }

    /**
     * Generate smart suggestions based on query patterns
     */
    private function generateSmartSuggestions(string $query, int $limit): array
    {
        $suggestions = [];
        
        // Common search patterns for water utility
        $patterns = [
            'ទឹក' => ['ទឹកស្អាត', 'ទឹកផ្គត់ផ្គង់', 'គុណភាពទឹក', 'តម្លៃទឹក', 'សេវាកម្មទឹក'],
            'សេវា' => ['សេវាកម្មទឹក', 'សេវាកម្មអតិថិជន', 'សេវាចំណាត់ការទឹក'],
            'របាយការណ៍' => ['របាយការណ៍ប្រចាំខែ', 'របាយការណ៍ប្រចាំឆ្នាំ', 'របាយការណ៍ហិរញ្ញវត្ថុ'],
            'តម្លៃ' => ['តម្លៃទឹក', 'តម្លៃសេវា', 'តម្លៃថ្នូរ'],
            'ការដាក់ពាក្យ' => ['ការដាក់ពាក្យសុំទឹក', 'ការដាក់ពាក្យបិទទឹក'],
            'បង្កាន់ដៃ' => ['បង្កាន់ដៃទឹក', 'ការទូទាត់បង្កាន់ដៃ'],
        ];

        foreach ($patterns as $keyword => $relatedTerms) {
            if (stripos($query, $keyword) !== false || stripos($keyword, $query) !== false) {
                foreach ($relatedTerms as $term) {
                    if (stripos($term, $query) !== false) {
                        $suggestions[] = [
                            'text' => $term,
                            'type' => 'smart',
                            'match_type' => 'pattern',
                            'category' => 'suggested'
                        ];
                    }
                }
            }
        }

        // Add trending/popular terms if query is short
        if (strlen($query) <= 2) {
            $popularTerms = ['ព័ត៌មានថ្មី', 'សេវាកម្មទឹក', 'តម្លៃទឹក', 'របាយការណ៍'];
            foreach ($popularTerms as $term) {
                if (stripos($term, $query) !== false) {
                    $suggestions[] = [
                        'text' => $term,
                        'type' => 'popular',
                        'match_type' => 'trending',
                        'category' => 'popular'
                    ];
                }
            }
        }

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Get a preview of content (first N characters with ellipsis)
     */
    private function getContentPreview(string $content, int $length = 150): string
    {
        // Strip HTML tags and get plain text
        $plainText = strip_tags($content);
        
        if (strlen($plainText) <= $length) {
            return $plainText;
        }
        
        return substr($plainText, 0, $length) . '...';
    }
}
