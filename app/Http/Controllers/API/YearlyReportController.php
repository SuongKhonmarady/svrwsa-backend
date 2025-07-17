<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\YearlyReport;
use App\Models\Year;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YearlyReportController extends Controller
{
    /**
     * Get all yearly reports with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = YearlyReport::with(['year'])
                ->where('status', 'published'); // Only published reports for public access
            
            // Apply filters
            if ($request->has('year')) {
                $query->byYear($request->year);
            }
            
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->byDateRange($request->start_date, $request->end_date);
            }
            
            // Order by year (descending)
            $query->join('years', 'yearly_reports.year_id', '=', 'years.id')
                  ->orderBy('years.year_value', 'desc')
                  ->select('yearly_reports.*');
            
            $reports = $query->paginate($request->get('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $reports,
                'message' => 'Published yearly reports retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving yearly reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all yearly reports including drafts (for authenticated staff)
     */
    public function indexAll(Request $request): JsonResponse
    {
        try {
            $query = YearlyReport::with(['year']); // No status filter - includes drafts
            
            // Apply filters
            if ($request->has('year')) {
                $query->byYear($request->year);
            }
            
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }
            
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->byDateRange($request->start_date, $request->end_date);
            }
            
            // Order by year (descending)
            $query->join('years', 'yearly_reports.year_id', '=', 'years.id')
                  ->orderBy('years.year_value', 'desc')
                  ->select('yearly_reports.*');
            
            $reports = $query->paginate($request->get('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $reports,
                'message' => 'All yearly reports retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving yearly reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new yearly report
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'year_id' => 'required|exists:years,id',
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
            
            // Check if report already exists for this year
            $existingReport = YearlyReport::where('year_id', $request->year_id)->first();
                
            if ($existingReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yearly report already exists for this year'
                ], 409);
            }
            
            $data = $request->only(['year_id', 'title', 'description', 'status', 'created_by']);
            $data['status'] = $data['status'] ?? 'draft';
            
            // Handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('yearly_reports', $filename, 'public');
                
                $data['file_url'] = Storage::url($path);
                $data['file_name'] = $file->getClientOriginalName();
                $data['file_size'] = $file->getSize();
            }
            
            $report = YearlyReport::create($data);
            $report->load(['year']);
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Yearly report created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating yearly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific yearly report (public access - only published)
     */
    public function show($id): JsonResponse
    {
        try {
            $report = YearlyReport::with(['year'])
                ->where('status', 'published') // Only published reports for public access
                ->findOrFail($id);
            
            // Add monthly reports completion status
            $report->monthly_completion = $report->getMonthlyCompletionStatus();
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Yearly report retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving yearly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a yearly report
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $report = YearlyReport::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'year_id' => 'sometimes|exists:years,id',
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'status' => 'sometimes|in:draft,published',
                'file' => 'sometimes|file|mimes:pdf,doc,docx|max:10240', // 10MB max
                'created_by' => 'sometimes|string|max:100'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if year is unique (if being updated)
            if ($request->has('year_id')) {
                $existingReport = YearlyReport::where('year_id', $request->year_id)
                    ->where('id', '!=', $id)
                    ->first();
                    
                if ($existingReport) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Yearly report already exists for this year'
                    ], 409);
                }
            }
            
            $data = $request->only(['year_id', 'title', 'description', 'status', 'created_by']);
            
            // Handle file upload
            if ($request->hasFile('file')) {
                // Delete old file if exists
                if ($report->file_url) {
                    $oldPath = str_replace('/storage/', '', $report->file_url);
                    Storage::disk('public')->delete($oldPath);
                }
                
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('yearly_reports', $filename, 'public');
                
                $data['file_url'] = Storage::url($path);
                $data['file_name'] = $file->getClientOriginalName();
                $data['file_size'] = $file->getSize();
            }
            
            // Set published_at if status changes to published
            if ($request->status === 'published' && $report->status !== 'published') {
                $data['published_at'] = now();
            } elseif ($request->status === 'draft') {
                $data['published_at'] = null;
            }
            
            $report->update($data);
            $report->load(['year']);
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Yearly report updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating yearly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a yearly report
     */
    public function destroy($id): JsonResponse
    {
        try {
            $report = YearlyReport::findOrFail($id);
            
            // Delete file if exists
            if ($report->file_url) {
                $path = str_replace('/storage/', '', $report->file_url);
                Storage::disk('public')->delete($path);
            }
            
            $report->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Yearly report deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting yearly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get yearly reports by year (public access - only published)
     */
    public function byYear($year): JsonResponse
    {
        try {
            $report = YearlyReport::byYear($year)
                ->with(['year'])
                ->where('status', 'published') // Only published reports for public access
                ->first();
            
            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'No published yearly report found for this year'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Published yearly report retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving yearly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish a yearly report
     */
    public function publish($id): JsonResponse
    {
        try {
            $report = YearlyReport::findOrFail($id);
            $report->publish();
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Yearly report published successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error publishing yearly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unpublish a yearly report
     */
    public function unpublish($id): JsonResponse
    {
        try {
            $report = YearlyReport::findOrFail($id);
            $report->unpublish();
            
            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Yearly report unpublished successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error unpublishing yearly report: ' . $e->getMessage()
            ], 500);
        }
    }
}
