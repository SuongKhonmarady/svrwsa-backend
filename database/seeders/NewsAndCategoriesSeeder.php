<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\News;
use Illuminate\Support\Str;

class NewsAndCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $categories = [
            ['name' => 'Technology'],
            ['name' => 'Health'],
            ['name' => 'Education'],
            ['name' => 'Business'],
            ['name' => 'Sports'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create sample news
        $newsItems = [
            [
                'title' => 'ព័ត៌មានទី ១',
                'content' => 'This is the first news content about technology updates.',
                'image' => 'tech-news.jpg',
                'published_at' => '2025-07-21',
                'featured' => true,
                'category_id' => 1,
            ],
            [
                'title' => 'Health Tips for Better Living',
                'content' => 'Important health information for everyone to stay healthy.',
                'image' => 'health-tips.jpg',
                'published_at' => '2025-07-20',
                'featured' => false,
                'category_id' => 2,
            ],
            [
                'title' => 'Education Reform Updates',
                'content' => 'Latest updates on the education system improvements.',
                'image' => 'education-news.jpg',
                'published_at' => '2025-07-19',
                'featured' => true,
                'category_id' => 3,
            ],
        ];

        foreach ($newsItems as $newsItem) {
            // Generate slug from title
            $newsItem['slug'] = Str::slug($newsItem['title']);
            News::create($newsItem);
        }
    }
}
