<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class News extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'image', 'published_at', 'featured', 'category_id'];
    
    protected $casts = [
        'featured' => 'boolean',
        'published_at' => 'date',
    ];

    // Remove automatic loading of category for faster inserts
    // protected $with = ['category'];

    // Relationship with Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Auto-generate slug from title
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = static::generateUniqueSlug($news->title);
            }
        });
        
        static::updating(function ($news) {
            if ($news->isDirty('title') || empty($news->slug)) {
                $news->slug = static::generateUniqueSlug($news->title, $news->id);
            }
        });
    }

    // Generate unique slug - Optimized version
    public static function generateUniqueSlug($title, $ignoreId = null)
    {
        $slug = Str::slug($title);
        
        // If slug is empty (e.g., non-Latin characters), use a fallback
        if (empty($slug)) {
            $slug = 'news-' . time();
        }
        
        // Get existing slugs in one query instead of multiple checks
        $existingSlugs = static::where('slug', 'LIKE', $slug . '%')
            ->when($ignoreId, function ($query, $ignoreId) {
                return $query->where('id', '!=', $ignoreId);
            })
            ->pluck('slug')
            ->toArray();
        
        // If no conflict, return original slug
        if (!in_array($slug, $existingSlugs)) {
            return $slug;
        }
        
        // Find the next available number
        $counter = 1;
        do {
            $newSlug = $slug . '-' . $counter;
            $counter++;
        } while (in_array($newSlug, $existingSlugs));

        return $newSlug;
    }

    // Route model binding by slug
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
