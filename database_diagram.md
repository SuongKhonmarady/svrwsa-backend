# Database Schema Diagram - SVRWSA Backend

## Database Tables Overview

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              SVRWSA Database Schema                             │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────┐         ┌─────────────────────────┐
│        USERS            │         │         NEWS            │
├─────────────────────────┤         ├─────────────────────────┤
│ 🔑 id (bigint, PK)      │         │ 🔑 id (bigint, PK)      │
│ 📝 name (varchar)       │         │ 📝 title (varchar)      │
│ 📧 email (varchar,UNIQ) │         │ 📄 content (text)       │
│ ⏰ email_verified_at    │         │ 🖼️ image (varchar, NULL) │
│ 🔒 password (varchar)   │         │ 📅 published_at (date)  │
│ 🎭 role (varchar)       │◄────────│ 🔗 created_by (bigint,FK)│
│ 🔑 remember_token       │  Admin  │ 🔗 updated_by (bigint,FK)│
│ ⏰ created_at           │ Manages │ ⏰ created_at           │
│ ⏰ updated_at           │  News   │ ⏰ updated_at           │
└─────────────────────────┘         └─────────────────────────┘

┌─────────────────────────┐         ┌─────────────────────────┐
│   PERSONAL_ACCESS_      │         │   PASSWORD_RESET_       │
│       TOKENS            │         │       TOKENS            │
├─────────────────────────┤         ├─────────────────────────┤
│ 🔑 id (bigint, PK)      │         │ 🔑 email (varchar, PK)  │
│ 🔗 tokenable_type       │         │ 🎫 token (varchar)      │
│ 🔗 tokenable_id         │         │ ⏰ created_at           │
│ 📝 name (text)          │         └─────────────────────────┘
│ 🎫 token (varchar,UNIQ) │
│ 🛡️ abilities (text)     │         ┌─────────────────────────┐
│ ⏰ last_used_at         │         │       SESSIONS          │
│ ⏰ expires_at           │         ├─────────────────────────┤
│ ⏰ created_at           │         │ 🔑 id (varchar, PK)     │
│ ⏰ updated_at           │         │ 🔗 user_id (bigint,FK)  │─────┐
└─────────────────────────┘         │ 🌐 ip_address (varchar) │     │
                                    │ 🖥️ user_agent (text)    │     │
┌─────────────────────────┐         │ 📦 payload (longtext)   │     │
│         CACHE           │         │ ⏰ last_activity (int)  │     │
├─────────────────────────┤         └─────────────────────────┘     │
│ 🔑 key (varchar, PK)    │                                         │
│ 💾 value (mediumtext)   │         ┌─────────────────────────┐     │
│ ⏰ expiration (int)     │         │         JOBS            │     │
└─────────────────────────┘         ├─────────────────────────┤     │
                                    │ 🔑 id (bigint, PK)      │     │
┌─────────────────────────┐         │ 📋 queue (varchar)      │     │
│      CACHE_LOCKS        │         │ 📦 payload (longtext)   │     │
├─────────────────────────┤         │ 🔄 attempts (tinyint)   │     │
│ 🔑 key (varchar, PK)    │         │ ⏰ reserved_at (int)    │     │
│ 👤 owner (varchar)      │         │ ⏰ available_at (int)   │     │
│ ⏰ expiration (int)     │         │ ⏰ created_at (int)     │     │
└─────────────────────────┘         └─────────────────────────┘     │
                                                                    │
┌─────────────────────────┐         ┌─────────────────────────┐     │
│      JOB_BATCHES        │         │      FAILED_JOBS        │     │
├─────────────────────────┤         ├─────────────────────────┤     │
│ 🔑 id (varchar, PK)     │         │ 🔑 id (bigint, PK)      │     │
│ 📝 name (varchar)       │         │ 🔗 uuid (varchar,UNIQ)  │     │
│ 📊 total_jobs (int)     │         │ 🔌 connection (text)    │     │
│ ⏳ pending_jobs (int)   │         │ 📋 queue (text)         │     │
│ ❌ failed_jobs (int)    │         │ 📦 payload (longtext)   │     │
│ 📝 failed_job_ids       │         │ ⚠️ exception (longtext)  │     │
│ ⚙️ options (mediumtext) │         │ ⏰ failed_at            │     │
│ ⏰ cancelled_at (int)   │         └─────────────────────────┘     │
│ ⏰ created_at (int)     │                                         │
│ ⏰ finished_at (int)    │                                         │
└─────────────────────────┘                                         │
                                                                    │
                                    ┌─────────────────────────┐     │
                                    │        USERS            │◄────┘
                                    │  (Reference Table)      │
                                    └─────────────────────────┘
```

## Table Details

### 🧑‍💼 Users Table
- **Primary Purpose**: User authentication and role management
- **Key Features**:
  - Role-based access control (admin, moderator, user)
  - Email verification support
  - Laravel Sanctum token authentication ready
- **Roles Available**: 
  - `admin` - Full administrative access
  - `moderator` - Limited administrative access  
  - `user` - Standard user access (default)

### 📰 News Table
- **Primary Purpose**: Content management for news articles
- **Key Features**:
  - Title and content storage
  - Optional image support
  - Publishing date control
  - **Admin tracking**: Records which admin created/updated each article
  - Timestamps for creation/modification tracking
- **Admin Relationships**:
  - `created_by` - Links to the admin who created the article
  - `updated_by` - Links to the admin who last updated the article

### 🔐 Authentication & Security Tables

#### Personal Access Tokens
- **Purpose**: API authentication via Laravel Sanctum
- **Features**: Token-based authentication with abilities and expiration

#### Password Reset Tokens
- **Purpose**: Secure password reset functionality
- **Features**: Time-limited reset tokens

#### Sessions
- **Purpose**: User session management
- **Features**: IP tracking, user agent logging, session payload storage
- **Relationship**: Links to Users table via `user_id`

### ⚙️ System Tables

#### Cache & Cache Locks
- **Purpose**: Application performance optimization
- **Features**: Key-value caching with expiration and locking mechanisms

#### Jobs, Job Batches & Failed Jobs
- **Purpose**: Background job processing
- **Features**: Queue management, batch processing, failure tracking

## Relationships

```
Users (1) ──────── (0..many) Sessions
   │
   │ (Polymorphic relationship)
   │
   ├──────── (0..many) Personal Access Tokens
   │
   │ (Admin manages News)
   │
   ├──────── (0..many) News (created_by)
   │
   └──────── (0..many) News (updated_by)
```

### Admin-News Management Flow:
1. **Create News**: Admin creates news article → `created_by` field set
2. **Update News**: Admin updates news article → `updated_by` field updated
3. **Publish Control**: Admin can set `published_at` date
4. **Full CRUD**: Admin has complete control over news content

## Security Features

- ✅ **Role-based Access Control**: Admin, Moderator, User roles
- ✅ **Email Uniqueness**: Prevents duplicate accounts
- ✅ **Password Hashing**: Secure password storage
- ✅ **Token Authentication**: Sanctum-based API tokens
- ✅ **Session Management**: Secure session handling
- ✅ **Remember Token**: Persistent login functionality

## Admin Middleware Integration

The `AdminMiddleware` checks:
1. User authentication status
2. User role via `isAdmin()` method
3. Returns appropriate HTTP status codes (401/403)

This ensures secure access to admin-only endpoints in your Laravel application.

## Admin News Management Features

### 🔐 Admin-Only Operations:
- ✅ **Create News**: Only admins can create new articles
- ✅ **Edit News**: Only admins can modify existing articles
- ✅ **Delete News**: Only admins can remove articles
- ✅ **Publish Control**: Only admins can set publication dates
- ✅ **Image Management**: Only admins can upload/manage news images

### 📊 Admin Tracking:
- **Audit Trail**: Track which admin created/updated each article
- **Accountability**: Know who published what and when
- **Content History**: Monitor admin activities on news content

### 🚀 Suggested Implementation:
```php
// In News model - add relationships
public function creator() {
    return $this->belongsTo(User::class, 'created_by');
}

public function updater() {
    return $this->belongsTo(User::class, 'updated_by');
}

// In User model - add news relationships
public function createdNews() {
    return $this->hasMany(News::class, 'created_by');
}

public function updatedNews() {
    return $this->hasMany(News::class, 'updated_by');
}
```

### 📝 Required Migration:
```php
// Add to news table migration
$table->foreignId('created_by')->nullable()->constrained('users');
$table->foreignId('updated_by')->nullable()->constrained('users');
```
