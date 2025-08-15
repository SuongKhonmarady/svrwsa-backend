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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\MonthlyReportRequest;

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
    public function store(MonthlyReportRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Securely add user attribution from the authenticated user's session.
            $data['created_by'] = auth()->user()->name;
            
            // Handle file upload before creating the record to avoid double activity logging
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                
                // Generate file path
                $year = Year::find($data['year_id']);
                $month = Month::find($data['month_id']);
                
                $fileName = $file->getClientOriginalName();
                $filePath = "monthly_reports/{$year->year_value}/" . strtolower($month->month) . "/{$fileName}";
                
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
                        'error' => 'Failed to upload file. The report was not created.'
                    ], 500);
                }
            }
            
            // Create report with all data including file info (single activity log entry)
            $report = MonthlyReport::create($data);
            $report->load(['year', 'month']);
            
            return response()->json(['success' => true, 'data' => $report, 'message' => 'Monthly report created successfully'], 201);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            // This will be triggered by MonthlyReportRequest if validation fails.
            return response()->json([
                'success' => false,
                'error' => 'Validation failed. Please check the form fields.',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating monthly report: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'error' => 'An unexpected error occurred while creating the report.'
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
    public function update(MonthlyReportRequest $request, $id): JsonResponse
    {
        try {
            $report = MonthlyReport::findOrFail($id);
            $data = $request->validated();
            
            // Securely add who updated the report.
            $data['updated_by'] = auth()->user()->name;
            
            if ($request->hasFile('file')) {
                // Delete old file from S3 if it exists before uploading the new one.
                if ($report->file_url) {
                    $report->deleteFileFromS3();
                }
                
                if (!$report->uploadFileToS3($request->file('file'))) {
                    return response()->json([
                        'success' => false, 
                        'error' => 'Failed to upload the new file.'
                    ], 500);
                }
            }
            
            // Handle the 'published_at' timestamp when status changes.
            if ($request->filled('status')) {
                if ($request->status === 'published' && $report->status !== 'published') {
                    $data['published_at'] = now();
                } elseif ($request->status === 'draft') {
                    $data['published_at'] = null;
                }
            }
            
            $report->update($data);
            $report->load(['year', 'month']);
            
            return response()->json(['success' => true, 'data' => $report, 'message' => 'Monthly report updated successfully']);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
             return response()->json([
                'success' => false,
                'error' => 'Validation failed. Please check the form fields.',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating monthly report: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'error' => 'An unexpected error occurred while updating the report.'
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
            
            // Store info before deletion
            $fileUrl = $report->file_url;
            $reportTitle = $report->title ?? 'Untitled';
            
            // Use database transaction for safer deletion
            $deleteTime = microtime(true);
            DB::transaction(function () use ($report) {
                $report->delete();
            }, 5); // 5 attempts with deadlock detection
            $deleteTime = microtime(true) - $deleteTime;
            
            // Log successful deletion
            Log::info("Monthly report deleted successfully", [
                'report_id' => $id,
                'report_title' => $reportTitle,
                'delete_time_ms' => round($deleteTime * 1000, 2),
                'had_file' => !empty($fileUrl)
            ]);
            
            $s3DeleteStatus = 'skipped';
            $attemptS3Cleanup = env('ATTEMPT_S3_CLEANUP_ON_DELETE', false);
            
            // Try to delete from S3 with timeout handling (if enabled)
            if ($attemptS3Cleanup && $fileUrl) {
                try {
                    $this->deleteReportFromS3WithTimeout($fileUrl, 3); // 3 second timeout
                    $s3DeleteStatus = 'completed';
                } catch (\Exception $s3Error) {
                    Log::warning("S3 cleanup failed but monthly report deleted successfully", [
                        'report_id' => $id,
                        'file_url' => $fileUrl,
                        'error' => $s3Error->getMessage()
                    ]);
                    $s3DeleteStatus = 'failed';
                }
            } else if ($fileUrl) {
                Log::info("S3 file marked for manual cleanup", [
                    'report_id' => $id,
                    'file_url' => $fileUrl
                ]);
            }
            
            $message = 'Monthly report deleted successfully';
            if ($s3DeleteStatus === 'failed') {
                $message .= ' (File cleanup will be handled automatically)';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'timing' => round($deleteTime * 1000, 2) . 'ms',
                'details' => [
                    'report_deleted' => true,
                    'file_cleanup' => $s3DeleteStatus
                ]
            ]);
        } catch (\Illuminate\Database\QueryException $dbError) {
            Log::error("Database error during monthly report deletion", [
                'report_id' => $id,
                'error' => $dbError->getMessage(),
                'sql_state' => $dbError->errorInfo[0] ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Database operation failed'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error("Error deleting monthly report", [
                'report_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error deleting monthly report: ' . $e->getMessage()
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
