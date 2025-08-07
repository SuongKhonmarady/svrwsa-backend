# News & Categories Setup Guide

## Summary of Changes Made

✅ **Added Category Model and Relationship**
- Created `Category` model with `hasMany` relationship to News
- News model has `belongsTo` relationship with Category

✅ **Added Featured Flag**
- Added `featured` boolean field to news (default: false)
- Important news can be marked as featured = true

✅ **Added Slug-based URL Access**
- Added `slug` field that auto-generates from title
- News can now be accessed via `/api/news/{slug}` instead of ID
- Automatic slug generation with duplicate handling

✅ **Combined Migrations**
- All news and categories migrations combined into one file
- Easy to migrate when cloning to other laptops

## Database Structure

### Categories Table
- `id` (primary key)
- `name` (string, unique)
- `created_at`, `updated_at`

### News Table  
- `id` (primary key)
- `title` (string)
- `slug` (string, unique) - auto-generated from title
- `content` (text)
- `image` (string, nullable)
- `published_at` (date, nullable)
- `featured` (boolean, default: false)
- `category_id` (foreign key to categories, nullable)
- `created_at`, `updated_at`

## API Endpoints

### Public Routes
- `GET /api/news` - List all news
- `GET /api/news/{slug}` - Get specific news by slug
- `GET /api/categories` - List all categories  
- `GET /api/categories/{id}` - Get specific category

### Admin Routes (Authentication Required)
- `POST /api/news` - Create news
- `PUT /api/news/{slug}` - Update news
- `DELETE /api/news/{slug}` - Delete news
- `POST /api/categories` - Create category
- `PUT /api/categories/{id}` - Update category  
- `DELETE /api/categories/{id}` - Delete category

## Example JSON Response

```json
{
  "id": 5,
  "title": "ព៍ត័មានទី ១",
  "slug": "ពត័មានទី-១",
  "content": "update news test update",
  "image": "test.jpg",
  "published_at": "2025-07-21",
  "featured": true,
  "category": {
    "id": 2,
    "name": "Technology"
  },
  "created_at": "2025-07-11T09:21:51.000000Z",
  "updated_at": "2025-08-04T08:29:05.000000Z"
}
```

## Setup Instructions for New Laptop

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd svrwsa-backend
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database** (update .env file with your database credentials)
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

## Files Modified/Created

### Models
- `app/Models/News.php` - Updated with slug generation, relationships, and featured flag
- `app/Models/Category.php` - New model for categories

### Controllers  
- `app/Http/Controllers/NewsController.php` - Updated for slug-based routing and new fields
- `app/Http/Controllers/CategoryController.php` - New controller for category management

### Migrations
- `database/migrations/2025_08_05_040000_create_news_and_categories_tables.php` - Combined migration

### Seeders
- `database/seeders/NewsAndCategoriesSeeder.php` - Sample data
- `database/seeders/DatabaseSeeder.php` - Updated to include new seeder

### Routes
- `routes/api.php` - Updated with slug-based routing and category routes

## Testing the Setup

After setup, you can test the endpoints:

1. **Get all news**: `GET http://localhost:8000/api/news`
2. **Get news by slug**: `GET http://localhost:8000/api/news/ពត័មានទី-១`
3. **Get all categories**: `GET http://localhost:8000/api/categories`

The response will include the category relationship and featured flag as shown in the example JSON above.
