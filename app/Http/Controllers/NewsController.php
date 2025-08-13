<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\News;

class NewsController extends Controller
{
    /**
     * Test S3 connection
     */
    public function testS3Connection()
    {
        try {
            // Test if we can list bucket contents
            $exists = Storage::disk('s3')->exists('');
            
            return response()->json([
                'success' => true,
                'message' => 'S3 connection successful',
                'bucket' => env('AWS_BUCKET'),
                'region' => env('AWS_DEFAULT_REGION')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'S3 connection failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()-> json(News::all());
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
        $validated = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB max
            'published_at' => 'nullable|date',
            'featured' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        // Handle image upload to S3
        if ($request->hasFile('image')) {
            try {
                $image = $request->file('image');
                $filename = 'news/' . time() . '_' . $image->getClientOriginalName();
                
                // Upload to S3
                $path = Storage::disk('s3')->putFileAs('', $image, $filename);
                
                // Set public visibility
                Storage::disk('s3')->setVisibility($path, 'public');
                
                // Get the full URL
                $validated['image'] = Storage::disk('s3')->url($path);
                
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to upload image to S3',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        $news = News::create($validated);

        return response()->json($news, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        return response()->json($news);
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
        $validated = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB max
            'remove_image' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'featured' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        // Handle image removal
        if ($request->boolean('remove_image') && $news->image) {
            try {
                // Extract the file path from URL
                $imagePath = $this->extractS3PathFromUrl($news->image);
                if ($imagePath && Storage::disk('s3')->exists($imagePath)) {
                    Storage::disk('s3')->delete($imagePath);
                }
                $validated['image'] = null;
            } catch (\Exception $e) {
                // Log error but continue with update
                \Log::warning('Failed to delete old image from S3: ' . $e->getMessage());
            }
        }

        // Handle new image upload
        if ($request->hasFile('image')) {
            try {
                // Delete old image if exists
                if ($news->image) {
                    $oldImagePath = $this->extractS3PathFromUrl($news->image);
                    if ($oldImagePath && Storage::disk('s3')->exists($oldImagePath)) {
                        Storage::disk('s3')->delete($oldImagePath);
                    }
                }

                // Upload new image
                $image = $request->file('image');
                $filename = 'news/' . time() . '_' . $image->getClientOriginalName();
                
                $path = Storage::disk('s3')->putFileAs('', $image, $filename);
                Storage::disk('s3')->setVisibility($path, 'public');
                
                $validated['image'] = Storage::disk('s3')->url($path);
                
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to upload image to S3',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        $news->update($validated);

        return response()->json($news);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(News $news)
    {
        try {
            // Delete associated image from S3 if exists
            if ($news->image) {
                $imagePath = $this->extractS3PathFromUrl($news->image);
                if ($imagePath && Storage::disk('s3')->exists($imagePath)) {
                    Storage::disk('s3')->delete($imagePath);
                }
            }

            $news->delete();

            return response()->json(['message' => 'News deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete news',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract S3 file path from full URL
     */
    private function extractS3PathFromUrl($url)
    {
        if (!$url) return null;
        
        $bucket = env('AWS_BUCKET');
        $region = env('AWS_DEFAULT_REGION');
        
        // Handle different S3 URL formats
        $patterns = [
            // https://bucket-name.s3.region.amazonaws.com/path/to/file
            "/https?:\/\/{$bucket}\.s3\.{$region}\.amazonaws\.com\/(.+)/",
            // https://s3.region.amazonaws.com/bucket-name/path/to/file
            "/https?:\/\/s3\.{$region}\.amazonaws\.com\/{$bucket}\/(.+)/",
            // https://bucket-name.s3.amazonaws.com/path/to/file (legacy)
            "/https?:\/\/{$bucket}\.s3\.amazonaws\.com\/(.+)/"
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
}
