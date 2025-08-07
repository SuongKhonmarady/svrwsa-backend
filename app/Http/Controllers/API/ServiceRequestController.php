<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\Status;
use App\Http\Requests\ServiceRequestFormRequest;
use Illuminate\Support\Facades\Storage;


class ServiceRequestController extends Controller
{
    /**
     * Display a listing of all service requests.
     */
    public function index()
    {
        $serviceRequests = ServiceRequest::with('status')->get();
        
        // Hide sensitive documents from non-admin users
        $serviceRequests->transform(function ($request) {
            $request->makeHidden(['id_card', 'family_book']);
            return $request;
        });
        
        return response()->json(['success' => true, 'data' => $serviceRequests]);
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

            // Handle ID Card uploads (front and back) - Store privately
            $idCardPaths = [];
            if ($request->hasFile('id_card')) {
                foreach ($request->file('id_card') as $index => $file) {
                    $side = $index === 0 ? 'front' : 'back';
                    $extension = $file->getClientOriginalExtension();
                    $fileName = $side . '.' . $extension;
                    // Store in private storage (not publicly accessible)
                    $path = $file->storeAs("id_docs/{$requestId}", $fileName);
                    $idCardPaths[] = $path; // Store the private path, not public URL
                }
            }

            // Handle Family Book uploads - Store privately
            $familyBookPaths = [];
            if ($request->hasFile('family_book')) {
                foreach ($request->file('family_book') as $index => $file) {
                    $extension = $file->getClientOriginalExtension();
                    $fileName = 'page' . ($index + 1) . '.' . $extension;
                    // Store in private storage (not publicly accessible)
                    $path = $file->storeAs("family_books/{$requestId}", $fileName);
                    $familyBookPaths[] = $path; // Store the private path, not public URL
                }
            }

            // Update service request with file paths
            $serviceRequest->update([
                'id_card' => $idCardPaths,
                'family_book' => $familyBookPaths,
            ]);
            
            // Load the status relationship
            $serviceRequest->load('status');

            return response()->json([
                'success' => true,
                'data' => $serviceRequest,
                'message' => 'Request submitted successfully with documents!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified service request with documents.
     */
    public function show($id)
    {
        try {
            $serviceRequest = ServiceRequest::with('status')->findOrFail($id);
            
            // Hide sensitive documents from non-admin users
            $serviceRequest->makeHidden(['id_card', 'family_book']);
            
            return response()->json([
                'success' => true,
                'data' => $serviceRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service request not found'
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
        $serviceRequests = ServiceRequest::with('status')->get();
        return response()->json(['success' => true, 'data' => $serviceRequests]);
    }

    /**
     * Admin only: Display the specified service request with documents.
     */
    public function adminShow($id)
    {
        try {
            $serviceRequest = ServiceRequest::with('status')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $serviceRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service request not found'
            ], 404);
        }
    }

    /**
     * Admin only: Serve document files securely.
     */
    public function serveDocument($id, $type, $filename)
    {
        try {
            // Verify service request exists
            $serviceRequest = ServiceRequest::findOrFail($id);
            
            // Construct file path based on type
            $allowedTypes = ['id_card', 'family_book'];
            if (!in_array($type, $allowedTypes)) {
                abort(404, 'Invalid document type');
            }
            
            // Map type to actual folder names in storage
            $folderMap = [
                'id_card' => 'id_docs',
                'family_book' => 'family_books'
            ];
            
            $folderName = $folderMap[$type];
            $filePath = "{$folderName}/{$id}/{$filename}";
            
            // Check if file exists
            if (!Storage::exists($filePath)) {
                abort(404, 'Document not found');
            }
            
            // Get the file
            $file = Storage::get($filePath);
            $mimeType = Storage::mimeType($filePath);
            
            return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            abort(404, 'Document not found');
        }
    }

}
