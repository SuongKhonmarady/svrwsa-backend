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

    protected $with = ['category'];

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

    // Generate unique slug
    public static function generateUniqueSlug($title, $ignoreId = null)
    {
        $slug = Str::slug($title);
        
        // If slug is empty (e.g., non-Latin characters), use a fallback
        if (empty($slug)) {
            $slug = 'news-' . time();
        }
        
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->when($ignoreId, function ($query, $ignoreId) {
            return $query->where('id', '!=', $ignoreId);
        })->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Route model binding by slug
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
