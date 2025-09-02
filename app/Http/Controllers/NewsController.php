<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = News::with('category');

            // Filter by category name if provided
            if ($request->has('category') && !empty($request->category)) {
                $categoryName = trim($request->category);
                $query->whereHas('category', function ($q) use ($categoryName) {
                    $q->where('name', 'LIKE', '%' . $categoryName . '%');
                });
            }

            $news = $query->orderBy('created_at', 'desc')->get();

            // Transform the data to make category information more prominent
            $transformedNews = $news->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'content' => $item->content,
                    'image' => $item->image,
                    'published_at' => $item->published_at,
                    'featured' => $item->featured,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'category' => $item->category ? [
                        'id' => $item->category->id,
                        'name' => $item->category->name,
                        'slug' => $item->category->name // Using name as slug for consistency
                    ] : null,
                    'category_id' => $item->category_id
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedNews,
                'filters' => [
                    'category' => $request->get('category'),
                    'total_count' => $news->count(),
                    'applied_filters' => $request->only(['category'])
                ],
                'category_info' => $request->has('category') ? [
                    'filtered_by' => $request->get('category'),
                    'category_details' => $news->first()?->category
                ] : null,
                'message' => $request->has('category') 
                    ? "News filtered by category: {$request->category}" 
                    : 'All news retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving news: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available categories for filtering
     */
    public function categories()
    {
        try {
            $categories = \App\Models\Category::select('id', 'name')
                ->withCount('news')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'news_count' => $category->news_count,
                        'slug' => $category->name // Using name as slug for consistency
                    ];
                }),
                'total_categories' => $categories->count(),
                'total_news' => $categories->sum('news_count'),
                'message' => 'Available categories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string',
                'content' => 'required|string',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB max
                'published_at' => 'nullable|date',
                'featured' => 'boolean',
                'category_id' => 'nullable|exists:categories,id',
            ]);

            $imageUploadStatus = 'no_image';

            // Handle image upload to S3 storage
            if ($request->hasFile('image')) {
                try {
                    $image = $request->file('image');
                    $filename = time().'_'.$image->getClientOriginalName();

                    // Store in S3 bucket under news directory
                    $path = $image->storeAs('news', $filename, 's3');
                    $validated['image'] = Storage::disk('s3')->url($path);

                    $imageUploadStatus = 'success';

                } catch (\Exception $e) {
                    Log::error('S3 storage upload failed', [
                        'error' => $e->getMessage(),
                        'filename' => $filename ?? 'unknown',
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to upload image',
                        'message' => 'Image upload failed: '.$e->getMessage(),
                    ], 500);
                }
            }

            // Create news record in database
            $news = DB::transaction(function () use ($validated) {
                return News::create($validated);
            }, 5);

            return response()->json([
                'success' => true,
                'message' => 'News created successfully',
                'data' => $news->load('category'),
                'details' => [
                    'image_upload' => $imageUploadStatus,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Unexpected error during news creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create news',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        return response()->json($news->load('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, News $news)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string',
                'content' => 'required|string',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB max
                'remove_image' => 'nullable|boolean',
                'published_at' => 'nullable|date',
                'featured' => 'boolean',
                'category_id' => 'nullable|exists:categories,id',
            ]);

            $imageUpdateStatus = 'no_change';
            $oldImageUrl = $news->image;

            // Handle image removal
            if ($request->boolean('remove_image') && $news->image) {
                try {
                    // Delete old image from S3 storage if it exists
                    $this->deleteS3Image($news->image);

                    $validated['image'] = null;
                    $imageUpdateStatus = 'removed';
                } catch (\Exception $e) {
                    // Continue with update even if image removal fails
                }
            }

            // Handle new image upload
            if ($request->hasFile('image')) {
                try {
                    $image = $request->file('image');
                    $filename = time().'_'.$image->getClientOriginalName();

                    // Store in S3 bucket under news directory
                    $path = $image->storeAs('news', $filename, 's3');
                    $validated['image'] = Storage::disk('s3')->url($path);

                    $imageUpdateStatus = 'updated';

                    // Delete old image from S3 storage if it exists
                    if ($oldImageUrl) {
                        try {
                            $this->deleteS3Image($oldImageUrl);
                        } catch (\Exception $e) {
                            // Continue even if old image deletion fails
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('S3 storage upload failed during news update', [
                        'news_id' => $news->id,
                        'error' => $e->getMessage(),
                        'filename' => $filename ?? 'unknown',
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to upload image',
                        'message' => 'Image upload failed: '.$e->getMessage(),
                    ], 500);
                }
            }

            // Update news record in database
            DB::transaction(function () use ($news, $validated) {
                $news->update($validated);
            }, 5); // 5 attempts with deadlock detection

            return response()->json([
                'success' => true,
                'message' => 'News updated successfully',
                'data' => $news->fresh()->load('category'), // Get fresh data from database with category
                'details' => [
                    'image_update' => $imageUpdateStatus,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Unexpected error during news update', [
                'news_id' => $news->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update news',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(News $news)
    {
        try {
            // Store info before deletion
            $imageUrl = $news->image;
            $newsId = $news->id;

            // Use database transaction for faster, safer deletion
            DB::transaction(function () use ($news) {
                $news->delete();
            }, 5); // 5 attempts with deadlock detection

            // Delete image from S3 storage if it exists
            $fileCleanupStatus = 'no_image';
            if ($imageUrl) {
                try {
                    $this->deleteS3Image($imageUrl);
                    $fileCleanupStatus = 'completed';
                } catch (\Exception $imageError) {
                    $fileCleanupStatus = 'failed';
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'News deleted successfully',
                'details' => [
                    'news_deleted' => true,
                    'file_cleanup' => $fileCleanupStatus,
                ],
            ]);

        } catch (\Illuminate\Database\QueryException $dbError) {
            Log::error('Database error during news deletion', [
                'news_id' => $news->id ?? 'unknown',
                'error' => $dbError->getMessage(),
                'sql_state' => $dbError->errorInfo[0] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Database operation failed',
                'message' => 'Unable to delete news due to database error',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Unexpected error during news deletion', [
                'news_id' => $news->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete news',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete image from S3 storage
     */
    private function deleteS3Image($imageUrl)
    {
        if (! $imageUrl) {
            return;
        }

        try {
            // Extract the path from the URL
            // URL format: https://bucket.s3.region.amazonaws.com/news/filename.jpg
            // We need to get: news/filename.jpg
            $path = parse_url($imageUrl, PHP_URL_PATH);

            if ($path) {
                // Remove leading slash to get the relative path
                $relativePath = ltrim($path, '/');

                // Check if file exists and delete it
                if (Storage::disk('s3')->exists($relativePath)) {
                    return Storage::disk('s3')->delete($relativePath);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete S3 image', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return false;
    }
}
