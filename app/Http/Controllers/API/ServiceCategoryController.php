<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Commune;
use App\Models\District;
use App\Models\Province;
use App\Models\Occupation;
use App\Models\UsageType;
use Illuminate\Http\JsonResponse;

class ServiceCategoryController extends Controller
{
    /**
     * Get all categories needed for service requests
     */
    public function index(): JsonResponse
    {
        try {
            $categories = [
                'communes' => Commune::select('id', 'name')->orderBy('name')->get(),
                'districts' => District::select('id', 'name')->orderBy('name')->get(),
                'provinces' => Province::select('id', 'name')->orderBy('name')->get(),
                'occupations' => Occupation::select('id', 'name')->orderBy('name')->get(),
                'usage_types' => UsageType::select('id', 'name')->orderBy('name')->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'All categories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific category type
     */
    public function show(string $type): JsonResponse
    {
        try {
            $allowedTypes = ['communes', 'districts', 'provinces', 'occupations', 'usage_types'];
            
            if (!in_array($type, $allowedTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid category type. Allowed types: ' . implode(', ', $allowedTypes)
                ], 400);
            }

            $modelMap = [
                'communes' => Commune::class,
                'districts' => District::class,
                'provinces' => Province::class,
                'occupations' => Occupation::class,
                'usage_types' => UsageType::class,
            ];

            $model = $modelMap[$type];
            $data = $model::select('id', 'name')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => ucfirst($type) . ' retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving ' . $type . ': ' . $e->getMessage()
            ], 500);
        }
    }
}
