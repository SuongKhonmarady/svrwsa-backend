<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\YearlyReport;
use App\Models\Year;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
            
            // $reports = $query->paginate($request->get('per_page', 15));
            
            $reports = $query->get();
            
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

            // Handle file upload before creating the record to avoid double activity logging
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                
                // Generate file path
                $year = Year::find($data['year_id']);
                
                $fileName = $file->getClientOriginalName();
                $filePath = "yearly_reports/{$year->year_value}/{$fileName}";
                
                // Store file to S3
                $storedPath = Storage::disk('s3')->putFileAs(
                    dirname($filePath),
                    $file,
                    basename($filePath)
                );
                
                if ($storedPath) {
                    // Add file information to data before creating record
                    $data['file_url'] = Storage::disk('s3')->url($storedPath);
                    $data['file_name'] = $file->getClientOriginalName();
                    $data['file_size'] = $file->getSize();
                } else {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Failed to upload file. The report was not created.'
                    ], 500);
                }
            }

            // Create report with all data including file info (single activity log entry)
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
            
            // Handle file upload using model method
            if ($request->hasFile('file')) {
                // Delete old file if exists
                if ($report->file_url) {
                    $report->deleteFileFromS3();
                }
                
                if (!$report->uploadFileToS3($request->file('file'))) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Failed to upload new file.'
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
            // Set longer execution time for S3 operations
            set_time_limit(60);
            
            $report = YearlyReport::findOrFail($id);
            
            // Delete from database first for faster user response
            $fileUrl = $report->file_url;
            $report->delete();
            
            // Try to delete from S3 with timeout handling
            if ($fileUrl) {
                try {
                    // Use timeout wrapper for S3 operations
                    $this->deleteReportFromS3WithTimeout($fileUrl, 15); // 15 second timeout
                } catch (\Exception $s3Error) {
                    // Log S3 error but don't fail the request since DB record is already deleted
                    \Log::warning("Failed to delete S3 file during yearly report deletion: " . $s3Error->getMessage(), [
                        'report_id' => $id,
                        'file_url' => $fileUrl
                    ]);
                }
            }
            
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
     * Delete report file from S3 with timeout handling
     */
    private function deleteReportFromS3WithTimeout($fileUrl, $timeout = 15)
    {
        try {
            // Set timeout for S3 operations
            ini_set('default_socket_timeout', $timeout);
            
            // Extract path from URL
            $parsedUrl = parse_url($fileUrl);
            $path = ltrim($parsedUrl['path'], '/');
            
            // Remove bucket name from path if present
            $bucketName = env('AWS_BUCKET');
            if (strpos($path, $bucketName . '/') === 0) {
                $path = substr($path, strlen($bucketName) + 1);
            }
            
            if (Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
            }
        } catch (\Exception $e) {
            // Reset timeout and re-throw
            ini_restore('default_socket_timeout');
            throw $e;
        }
        
        // Reset timeout
        ini_restore('default_socket_timeout');
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
