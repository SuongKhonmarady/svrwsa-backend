# Testing PDF File Upload with Postman

## 📋 Prerequisites

1. **Laravel server running**: `php artisan serve`
2. **Authentication token**: Get token from login endpoint
3. **PDF file**: Prepare a PDF file for testing

## 🔐 Step 1: Get Authentication Token

### Login Request
```
POST http://localhost:8000/api/login
Content-Type: application/json

{
    "email": "your-email@example.com",
    "password": "your-password"
}
```

### Response
```json
{
    "success": true,
    "user": {...},
    "token": "your-token-here"
}
```

## 📄 Step 2: Create Report with PDF File

### Request Configuration
```
POST http://localhost:8000/api/admin/reports/monthly
Authorization: Bearer your-token-here
Content-Type: multipart/form-data
```

### Form Data Fields:
- `year_id`: 12 (for 2025)
- `month_id`: 12 (for December)
- `title`: "December 2025 Water Quality Report"
- `description`: "Comprehensive water quality analysis for December 2025"
- `status`: "draft" or "published"
- `created_by`: "Admin User"
- `file`: [Select PDF file]

### Postman Steps:
1. Set method to **POST**
2. Enter URL: `http://localhost:8000/api/admin/reports/monthly`
3. Go to **Authorization** tab → Select **Bearer Token** → Paste your token
4. Go to **Body** tab → Select **form-data**
5. Add text fields:
   - `year_id`: 12
   - `month_id`: 12
   - `title`: December 2025 Water Quality Report
   - `description`: Comprehensive water quality analysis for December 2025
   - `status`: draft
   - `created_by`: Admin User
6. Add file field:
   - Key: `file`
   - Type: File
   - Select your PDF file

## 📝 Step 3: Update Report with PDF File

### Request Configuration
```
PUT http://localhost:8000/api/admin/reports/monthly/{report_id}
Authorization: Bearer your-token-here
Content-Type: multipart/form-data
```

### Form Data Fields:
- `title`: "Updated December 2025 Water Quality Report"
- `description`: "Updated comprehensive water quality analysis"
- `status`: "published"
- `file`: [Select new PDF file]

### Postman Steps:
1. Set method to **PUT**
2. Enter URL: `http://localhost:8000/api/admin/reports/monthly/39` (replace 39 with actual report ID)
3. Set Authorization header with Bearer token
4. Go to **Body** tab → Select **form-data**
5. Add fields you want to update
6. Add file field if uploading new PDF

## 🔍 Step 4: Verify PDF Upload

### Get Report Details
```
GET http://localhost:8000/api/reports/monthly/{report_id}
```

### Expected Response:
```json
{
    "success": true,
    "data": {
        "id": 37,
        "title": "Monthly Water Quality Report - November 2025",
        "file_url": "https://svr-wsa-reports.s3.us-east-1.amazonaws.com/monthly_reports/2025/november/Final Slides_ScholarTrack Transparent Scholarship Data hub.pdf",
        "file_name": "Final Slides_ScholarTrack Transparent Scholarship Data hub.pdf",
        "file_size": 5242880,
        "formatted_file_size": "5 MB"
    }
}
```

## 📊 Step 5: Test PDF Access

### Direct S3 Access (with signed URL)
The `file_url` returned will be a signed URL that can be accessed directly:
```
GET https://svr-wsa-reports.s3.us-east-1.amazonaws.com/monthly_reports/2025/december/report.pdf
```

## 🛠️ Common Issues & Solutions

### 1. **File Too Large**
- Error: "The file may not be greater than 10240 kilobytes"
- Solution: Reduce PDF size or increase limit in controller

### 2. **Invalid File Type**
- Error: "The file must be a file of type: pdf, doc, docx, txt"
- Solution: Ensure file is PDF format

### 3. **Authentication Error**
- Error: "Unauthenticated"
- Solution: Check Bearer token in Authorization header

### 4. **S3 Upload Failed**
- Error: "Failed to upload file to S3"
- Solution: Check AWS credentials and S3 bucket permissions

## 🎯 Testing Scenarios

### Scenario 1: Create Report with PDF
1. Login to get token
2. Create report with PDF attachment
3. Verify file is uploaded to S3
4. Check file URL is accessible

### Scenario 2: Update Report PDF
1. Get existing report ID
2. Update with new PDF file
3. Verify old file is replaced
4. Check new file URL

### Scenario 3: Remove PDF File
1. Update report without file field
2. Verify file_url becomes null
3. Check old file is deleted from S3

## 📚 API Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/admin/reports/monthly` | Create report with PDF |
| PUT | `/api/admin/reports/monthly/{id}` | Update report with PDF |
| GET | `/api/reports/monthly/{id}` | Get report details |
| DELETE | `/api/admin/reports/monthly/{id}` | Delete report and PDF |

## 🔧 Troubleshooting

### Debug Mode
Add to your request headers:
```
Accept: application/json
X-Requested-With: XMLHttpRequest
```

### Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

### Verify S3 Configuration
```bash
php artisan config:clear
php artisan config:cache
```
