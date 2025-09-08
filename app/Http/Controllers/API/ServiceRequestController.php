<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequestFormRequest;
use App\Models\ServiceRequest;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceRequestController extends Controller
{
    /**
     * Admin only: Display a listing of all service requests with filtering.
     * Supports filtering by name and phone parameters.
     */
    public function index(Request $request)
    {
        $query = ServiceRequest::with([
            'status',
            'commune', 
            'district',
            'province',
            'occupation',
            'usageType'
        ]);

        // Filter by name if provided
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }

        // Filter by phone if provided
        if ($request->has('phone') && !empty($request->phone)) {
            $query->where('phone', 'LIKE', '%' . $request->phone . '%');
        }

        $serviceRequests = $query->get();

        // Admin can see all data including sensitive documents
        return response()->json([
            'success' => true, 
            'data' => $serviceRequests,
            'filters' => [
                'name' => $request->name,
                'phone' => $request->phone
            ],
            'count' => $serviceRequests->count()
        ]);
    }

    public function store(ServiceRequestFormRequest $request)
    {
        try {
            $data = $request->validated();

            // Dynamically get the ID of the "Pending" status
            $pendingStatus = \App\Models\Status::where('name', 'Pending')->first();
            $data['status_id'] = $pendingStatus->id ?? 1; // fallback to ID 1 if not found

            // Create service request first to get the ID for organizing files
            $serviceRequest = ServiceRequest::create(collect($data)->except(['id_card', 'family_book'])->toArray());
            $requestId = $serviceRequest->id;

            // Handle ID Card uploads (front and back) - Store privately in S3
            $idCardPaths = [];
            if ($request->hasFile('id_card')) {
                foreach ($request->file('id_card') as $index => $file) {
                    $side = $index === 0 ? 'front' : 'back';
                    $extension = $file->getClientOriginalExtension();
                    $fileName = $side.'.'.$extension;
                    // Store in private S3 storage using dedicated private disk
                    $path = $file->storeAs("service_requests/{$requestId}/id_card", $fileName, 's3-private');
                    $idCardPaths[] = $path; // Store the private path, not public URL
                }
            }

            // Handle Family Book uploads - Store privately in S3
            $familyBookPaths = [];
            if ($request->hasFile('family_book')) {
                foreach ($request->file('family_book') as $index => $file) {
                    $extension = $file->getClientOriginalExtension();
                    $fileName = 'page'.($index + 1).'.'.$extension;
                    // Store in private S3 storage using dedicated private disk
                    $path = $file->storeAs("service_requests/{$requestId}/family_books", $fileName, 's3-private');
                    $familyBookPaths[] = $path; // Store the private path, not public URL
                }
            }

            // Update service request with file paths
            $serviceRequest->update([
                'id_card' => $idCardPaths,
                'family_book' => $familyBookPaths,
            ]);

            // Load all relationships
            $serviceRequest->load([
                'status',
                'commune',
                'district', 
                'province',
                'occupation',
                'usageType'
            ]);

            return response()->json([
                'success' => true,
                'data' => $serviceRequest,
                'message' => 'Request submitted successfully with documents!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin only: Display service requests filtered by status with documents.
     */
    public function adminGetByStatus(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string|in:Pending,In Progress,Completed,Rejected',
        ]);

        $statusName = $request->status;
        
        if ($statusName) {
            // Filter by specific status
            $serviceRequests = ServiceRequest::whereHas('status', function ($query) use ($statusName) {
                $query->where('name', $statusName);
            })->with([
                'status',
                'commune',
                'district',
                'province',
                'occupation',
                'usageType'
            ])->get();
        } else {
            // Return all service requests
            $serviceRequests = ServiceRequest::with([
                'status',
                'commune',
                'district',
                'province',
                'occupation',
                'usageType'
            ])->get();
        }

        return response()->json([
            'success' => true, 
            'data' => $serviceRequests,
            'status' => $statusName ?: 'All',
            'count' => $serviceRequests->count()
        ]);
    }

    /**
     * Display the specified service request with documents.
     */
    public function show($id)
    {
        try {
            $serviceRequest = ServiceRequest::with([
                'status',
                'commune',
                'district',
                'province',
                'occupation',
                'usageType'
            ])->findOrFail($id);

            // Hide sensitive documents from non-admin users
            $serviceRequest->makeHidden(['id_card', 'family_book']);

            return response()->json([
                'success' => true,
                'data' => $serviceRequest,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service request not found',
            ], 404);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_id' => 'required|exists:statuses,id',
        ]);

        $serviceRequest = ServiceRequest::with('status')->findOrFail($id);
        $serviceRequest->status_id = $request->status_id;
        $serviceRequest->save();

        // Reload the status relationship after saving
        $serviceRequest->load('status');

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully!',
            'data' => $serviceRequest,
        ]);
    }

    /**
     * Admin only: Display all service requests with documents.
     */
    public function adminIndex()
    {
        $serviceRequests = ServiceRequest::with([
            'status',
            'commune',
            'district',
            'province',
            'occupation',
            'usageType'
        ])->get();

        return response()->json(['success' => true, 'data' => $serviceRequests]);
    }

    /**
     * Admin only: Display the specified service request with documents.
     */
    public function adminShow($id)
    {
        try {
            $serviceRequest = ServiceRequest::with([
                'status',
                'commune',
                'district',
                'province',
                'occupation',
                'usageType'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $serviceRequest,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service request not found',
            ], 404);
        }
    }

    /**
     * Admin only: Serve document files securely from S3.
     */
    public function serveDocument($id, $type, $filename)
    {
        try {
            // Verify service request exists
            $serviceRequest = ServiceRequest::findOrFail($id);

            // Construct file path based on type
            $allowedTypes = ['id_card', 'family_book'];
            if (! in_array($type, $allowedTypes)) {
                abort(404, 'Invalid document type');
            }

            // Try new path structure first
            $storageType = $type === 'family_book' ? 'family_books' : $type;
            $newFilePath = "service_requests/{$id}/{$storageType}/{$filename}";
            
            // Check if file exists in new structure
            if (Storage::disk('s3-private')->exists($newFilePath)) {
                $filePath = $newFilePath;
            } else {
                // Fall back to old structure for backward compatibility
                $oldFolderMap = [
                    'id_card' => 'id_docs',
                    'family_book' => 'family_books',
                ];
                $oldFolderName = $oldFolderMap[$type];
                $filePath = "{$oldFolderName}/{$id}/{$filename}";
                
                // Check if file exists in old structure
                if (!Storage::disk('s3-private')->exists($filePath)) {
                    abort(404, 'Document not found');
                }
            }

            // Get the file from private S3
            $file = Storage::disk('s3-private')->get($filePath);
            $mimeType = Storage::disk('s3-private')->mimeType($filePath);

            return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="'.$filename.'"');

        } catch (\Exception $e) {
            abort(404, 'Document not found');
        }
    }

    /**
     * Admin only: Delete a service request and its documents.
     */
    public function destroy($id)
    {
        try {
            $serviceRequest = ServiceRequest::findOrFail($id);

            // Store document paths before deletion
            $idCardPaths = $serviceRequest->id_card ?? [];
            $familyBookPaths = $serviceRequest->family_book ?? [];

            // Delete the service request from database
            $serviceRequest->delete();

            // Delete documents from S3 storage
            $this->deleteServiceRequestDocuments($idCardPaths, $familyBookPaths);

            return response()->json([
                'success' => true,
                'message' => 'Service request and documents deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service request',
            ], 500);
        }
    }

    /**
     * Delete service request documents from S3 private storage.
     */
    private function deleteServiceRequestDocuments(array $idCardPaths = [], array $familyBookPaths = []): void
    {
        try {
            // Delete ID card documents from private S3
            foreach ($idCardPaths as $path) {
                if (Storage::disk('s3-private')->exists($path)) {
                    Storage::disk('s3-private')->delete($path);
                }
            }

            // Delete family book documents from private S3
            foreach ($familyBookPaths as $path) {
                if (Storage::disk('s3-private')->exists($path)) {
                    Storage::disk('s3-private')->delete($path);
                }
            }
        } catch (\Exception $e) {
            // Continue silently - document cleanup failure shouldn't stop the main deletion process
        }
    }
}
