# Dashboard API Documentation

This document describes the dashboard API endpoints for the admin frontend.

## Base URL
All endpoints are prefixed with `/api/admin/dashboard/` and require admin authentication.

## Authentication
All dashboard endpoints require:
- Authentication via Sanctum token
- Admin role

## Endpoints

### 1. Dashboard Statistics
**GET** `/api/admin/dashboard/stats`

Returns overview statistics for the dashboard.

**Response Example:**
```json
{
  "success": true,
  "data": {
    "total_users": 3,
    "total_news": 7,
    "published_news": 5,
    "total_service_requests": 15,
    "pending_service_requests": 8,
    "total_monthly_reports": 12,
    "published_monthly_reports": 10,
    "total_yearly_reports": 3,
    "published_yearly_reports": 2,
    "recent_service_requests": 3,
    "recent_news": 2
  }
}
```

### 2. Customer Growth Data
**GET** `/api/admin/dashboard/customer-growth/{year?}`

Returns customer (service request) growth data by month for a specific year.

**Parameters:**
- `year` (optional): Year to get data for. Defaults to current year.

**Response Example:**
```json
{
  "success": true,
  "data": {
    "year": "2025",
    "monthly_data": [
      {
        "month": 1,
        "month_name": "January",
        "count": 5
      },
      {
        "month": 2,
        "month_name": "February",
        "count": 3
      },
      // ... other months
    ],
    "total_for_year": 28
  }
}
```

### 3. Recent News
**GET** `/api/admin/dashboard/recent-news?limit=4`

Returns recent published news articles.

**Parameters:**
- `limit` (optional): Number of news items to return. Defaults to 4.

**Response Example:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Latest News Title",
      "slug": "latest-news-title",
      "content": "Full content of the news...",
      "excerpt": "Brief excerpt of the news content...",
      "image": "news-image.jpg",
      "published_at": "2025-08-15",
      "featured": true,
      "category": {
        "id": 1,
        "name": "General",
        "slug": "general"
      },
      "created_at": "2025-08-15T10:30:00.000000Z",
      "updated_at": "2025-08-15T10:30:00.000000Z"
    }
    // ... more news items
  ]
}
```

### 4. Recent Reports
**GET** `/api/admin/dashboard/recent-reports?limit=3`

Returns recent published reports (both monthly and yearly).

**Parameters:**
- `limit` (optional): Number of reports to return. Defaults to 3.

**Response Example:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Monthly Report Title",
      "type": "monthly",
      "period": "August 2025",
      "month": "August",
      "year": "2025",
      "is_published": true,
      "created_at": "2025-08-15T10:30:00.000000Z",
      "updated_at": "2025-08-15T10:30:00.000000Z"
    },
    {
      "id": 2,
      "title": "Yearly Report Title",
      "type": "yearly",
      "period": "2024",
      "year": "2024",
      "is_published": true,
      "created_at": "2025-08-10T14:20:00.000000Z",
      "updated_at": "2025-08-10T14:20:00.000000Z"
    }
    // ... more reports
  ]
}
```

## Error Responses

All endpoints return error responses in the following format:

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message (in development mode)"
}
```

## Frontend Integration

### JavaScript Example (using fetch)

```javascript
// Dashboard API class for frontend
class DashboardAPI {
  constructor(baseURL, token) {
    this.baseURL = baseURL;
    this.token = token;
  }

  async get(endpoint) {
    const response = await fetch(`${this.baseURL}${endpoint}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });
    return response.json();
  }

  // Dashboard API Methods
  async getDashboardStats() {
    return this.get('/admin/dashboard/stats');
  }

  async getCustomerGrowthData(year = new Date().getFullYear()) {
    return this.get(`/admin/dashboard/customer-growth/${year}`);
  }

  async getRecentNews(limit = 4) {
    return this.get(`/admin/dashboard/recent-news?limit=${limit}`);
  }

  async getRecentReports(limit = 3) {
    return this.get(`/admin/dashboard/recent-reports?limit=${limit}`);
  }
}

// Usage example
const api = new DashboardAPI('http://your-api-url/api', 'your-auth-token');

// Get dashboard stats
api.getDashboardStats().then(response => {
  if (response.success) {
    console.log('Dashboard stats:', response.data);
  }
});

// Get customer growth for 2025
api.getCustomerGrowthData(2025).then(response => {
  if (response.success) {
    console.log('Customer growth data:', response.data);
  }
});
```

## Notes

1. All endpoints require admin authentication.
2. The customer growth data is based on service request creation dates.
3. Recent news only includes published articles.
4. Recent reports only includes published reports.
5. All dates are returned in ISO 8601 format.
6. The excerpts for news are automatically generated from the content (max 150 characters).
