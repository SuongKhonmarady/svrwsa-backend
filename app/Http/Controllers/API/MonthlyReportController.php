<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MonthlyReport;
use App\Models\Year;
use App\Models\Month;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MonthlyReportController extends Controller
{
    /**
     * Get all monthly reports with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = MonthlyReport::with(['year', 'month'])
                ->where('status', 'published'); // Only published reports for public access
            
            // Apply filters
            if ($request->has('year')) {
                $query->byYear($request->year);
            }
            
            if ($request->has('month')) {
                $query->byMonth($request->month);
            }
            
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->byDateRange($request->start_date, $request->end_date);
            }
            
            // Order by year and month
            $query->join('years', 'monthly_reports.year_id', '=', 'years.id')
                  ->join('months', 'monthly_reports.month_id', '=', 'months.id')
                  ->orderBy('years.year_value', 'desc')
                  ->orderBy('months.id', 'desc')
                  ->select('monthly_reports.*');
            
            $reports = $query->paginate($request->get('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $reports,
                'message' => 'Published monthly reports retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all monthly reports including drafts (for authenticated staff)
     */
    public function indexAll(Request $request): JsonResponse
    {
        try {
            $query = MonthlyReport::with(['year', 'month']); // No status filter - includes drafts
            
            // Apply filters
            if ($request->has('year')) {
                $query->byYear($request->year);
            }
            
            if ($request->has('month')) {
                $query->byMonth($request->month);
            }
            
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }
            
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->byDateRange($request->start_date, $request->end_date);
            }
            
            // Order by year and month
            $query->join('years', 'monthly_reports.year_id', '=', 'years.id')
                  ->join('months', 'monthly_reports.month_id', '=', 'months.id')
                  ->orderBy('years.year_value', 'desc')
                  ->orderBy('months.id', 'desc')
                  ->select('monthly_reports.*');
            
            $reports = $query->paginate($request->get('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $reports,
                'message' => 'All monthly reports retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new monthly report
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'year_id' => 'required|exists:years,id',
                'month_id' => 'required|exists:months,id',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'status' => 'in:draft,published',
                'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // 10MB max
                'created_by' => 'required|string|max:100'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if report already exists for this year/month
            $existingReport = MonthlyReport::where('year_id', $request->year_id)
                ->where('month_id', $request->month_id)
                ->first();
                
            if ($existingReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Monthly report already exists for this period'
                ], 409);
            }
            
            $data = $request->only(['year_id', 'month_id', 'title', 'description', 'status', 'created_by']);
            $data['status'] = $data['status'] ?? 'draft';
            
            // Create the report first
            $report = MonthlyReport::create($data);
            
            // Handle file upload to S3
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $uploaded = $report->uploadFileToS3($file);
                
                if (!$uploaded) {
                    $report->delete(); // Clean up if file upload fails
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload file to S3'
                    ], 500);
                }
            }
            
            $report->load(['year', 'month']);
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Monthly report created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating monthly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific monthly report (public access - only published)
     */
    public function show($id): JsonResponse
    {
        try {
            $report = MonthlyReport::with(['year', 'month'])
                ->where('status', 'published') // Only published reports for public access
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Monthly report retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a monthly report
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $report = MonthlyReport::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'year_id' => 'sometimes|exists:years,id',
                'month_id' => 'sometimes|exists:months,id',
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'status' => 'sometimes|in:draft,published',
                'file' => 'sometimes|file|mimes:pdf,doc,docx,txt|max:10240', // 10MB max
                'created_by' => 'sometimes|string|max:100'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if year/month combination is unique (if being updated)
            if ($request->has('year_id') || $request->has('month_id')) {
                $yearId = $request->get('year_id', $report->year_id);
                $monthId = $request->get('month_id', $report->month_id);
                
                $existingReport = MonthlyReport::where('year_id', $yearId)
                    ->where('month_id', $monthId)
                    ->where('id', '!=', $id)
                    ->first();
                    
                if ($existingReport) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Monthly report already exists for this period'
                    ], 409);
                }
            }
            
            $data = $request->only(['year_id', 'month_id', 'title', 'description', 'status', 'created_by']);
            
            // Handle file upload
            if ($request->hasFile('file')) {
                // Delete old file from S3 if exists
                if ($report->file_url) {
                    $report->deleteFileFromS3();
                }
                
                $file = $request->file('file');
                $uploaded = $report->uploadFileToS3($file);
                
                if (!$uploaded) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload file to S3'
                    ], 500);
                }
            }
            
            // Set published_at if status changes to published
            if ($request->status === 'published' && $report->status !== 'published') {
                $data['published_at'] = now();
            } elseif ($request->status === 'draft') {
                $data['published_at'] = null;
            }
            
            $report->update($data);
            $report->load(['year', 'month']);
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Monthly report updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating monthly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a monthly report
     */
    public function destroy($id): JsonResponse
    {
        try {
            $report = MonthlyReport::findOrFail($id);
            
            // Delete file if exists
            if ($report->file_url) {
                $path = str_replace('/storage/', '', $report->file_url);
                Storage::disk('public')->delete($path);
            }
            
            $report->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Monthly report deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting monthly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly reports by year (public access - only published)
     */
    public function byYear($year): JsonResponse
    {
        try {
            $reports = MonthlyReport::byYear($year)
                ->with(['year', 'month'])
                ->where('status', 'published') // Only published reports for public access
                ->orderBy('month_id')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $reports,
                'message' => 'Published monthly reports retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish a monthly report
     */
    public function publish($id): JsonResponse
    {
        try {
            $report = MonthlyReport::findOrFail($id);
            $report->publish();
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Monthly report published successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error publishing monthly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unpublish a monthly report
     */
    public function unpublish($id): JsonResponse
    {
        try {
            $report = MonthlyReport::findOrFail($id);
            $report->unpublish();
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Monthly report unpublished successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error unpublishing monthly report: ' . $e->getMessage()
            ], 500);
        }
    }
}
