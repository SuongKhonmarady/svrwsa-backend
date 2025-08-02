<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\Status;
use App\Http\Requests\ServiceRequestFormRequest;


class ServiceRequestController extends Controller
{
    /**
     * Display a listing of all service requests.
     */
    public function index()
    {
        $serviceRequests = ServiceRequest::with('status')->get();
        return response()->json(['success' => true, 'data' => $serviceRequests]);
    }
    public function store(ServiceRequestFormRequest $request)
    {
        $data = $request->validated();

        // Dynamically get the ID of the "Pending" status
        $pendingStatus = \App\Models\Status::where('name', 'Pending')->first();
        $data['status_id'] = $pendingStatus->id ?? 1; // fallback to ID 1 if not found

        $serviceRequest = ServiceRequest::create($data);
        
        // Load the status relationship
        $serviceRequest->load('status');

        return response()->json([
            'success' => true,
            'data' => $serviceRequest,
            'message' => 'Request submitted!'
        ]);
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

}
