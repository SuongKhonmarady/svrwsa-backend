# Enhanced Service Request System with Document Upload

## Overview
The customer service request system has been enhanced to support document uploads, specifically:
- **ID Card**: Front and back images (exactly 2 files required)
- **Family Book (សៀវភៅគ្រួសារ)**: One or more pages/images

## Database Changes

### Migration
- Added `id_card` JSON field for storing ID card image URLs
- Added `family_book` JSON field for storing family book image URLs

### Model Updates
- Updated `ServiceRequest` model to include new fillable fields
- Added JSON casting for `id_card` and `family_book` fields

## API Endpoints

### POST /api/service-requests
Submit a new service request with documents.

**Required Fields:**
- `name` (string, max 255)
- `service_type` (string, max 255)
- `id_card[]` (array of 2 files: front and back)
- `family_book[]` (array of min 1 file)

**Optional Fields:**
- `email` (valid email)
- `phone` (string, max 20)
- `address` (string, max 500)
- `details` (string, max 2000)

**File Validation:**
- ID Card: JPG, JPEG, PNG only, max 2MB each
- Family Book: JPG, JPEG, PNG, PDF, max 2MB each

### GET /api/service-requests
Get all service requests with their documents.

### GET /api/service-requests/{id}
Get a specific service request with documents.

### PATCH /api/service-requests/{id}/status
Update service request status (requires authentication).

## File Storage Structure

```
storage/app/public/
├── id_docs/
│   └── {service_request_id}/
│       ├── front.jpg
│       └── back.jpg
└── family_books/
    └── {service_request_id}/
        ├── page1.jpg
        ├── page2.jpg
        └── page3.pdf
```

## Public Access URLs

Files are accessible via:
- `http://yourdomain.com/storage/id_docs/{id}/front.jpg`
- `http://yourdomain.com/storage/family_books/{id}/page1.jpg`

## Response Format

```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "Suong Khonmarady",
    "email": "test@gmail.com",
    "phone": "0987654321",
    "address": "Phnom Penh",
    "service_type": "Water Supply",
    "details": "Need new water connection",
    "id_card": [
      "https://yourdomain.com/storage/id_docs/3/front.jpg",
      "https://yourdomain.com/storage/id_docs/3/back.jpg"
    ],
    "family_book": [
      "https://yourdomain.com/storage/family_books/3/page1.jpg",
      "https://yourdomain.com/storage/family_books/3/page2.jpg"
    ],
    "created_at": "2025-08-01T10:02:30.000000Z",
    "updated_at": "2025-08-01T10:02:30.000000Z",
    "status": {
      "id": 9,
      "name": "Pending"
    }
  },
  "message": "Request submitted successfully with documents!"
}
```

## Testing

### Available Test Files
1. **test_document_upload.php** - Command line test documentation
2. **Service_Request_API.postman_collection.json** - Postman collection
3. **public/service-request-form.html** - Web form for testing

### Testing Steps

1. **Start the server:**
   ```bash
   php artisan serve
   ```

2. **Test with cURL:**
   ```bash
   curl -X POST http://localhost:8000/api/service-requests \
     -F 'name=Suong Khonmarady' \
     -F 'email=test@gmail.com' \
     -F 'service_type=Water Supply' \
     -F 'id_card[]=@/path/to/front.jpg' \
     -F 'id_card[]=@/path/to/back.jpg' \
     -F 'family_book[]=@/path/to/page1.jpg'
   ```

3. **Test with HTML form:**
   Navigate to `http://localhost:8000/service-request-form.html`

4. **Test with Postman:**
   Import the provided Postman collection file

## Security Features

- File type validation (only allowed image/PDF formats)
- File size limits (2MB per file)
- Organized storage by service request ID
- Public storage link for file access
- Input validation and sanitization

## Error Handling

- Comprehensive validation messages
- File upload error handling
- Database transaction safety
- Proper HTTP status codes

## Files Modified/Created

### Core Files Modified:
- `app/Models/ServiceRequest.php` - Added new fields and casting
- `app/Http/Controllers/API/ServiceRequestController.php` - Enhanced with file upload
- `app/Http/Requests/ServiceRequestFormRequest.php` - Added file validation
- `routes/api.php` - Added show route
- `database/migrations/2025_08_03_080307_add_document_fields_to_service_requests_table.php` - New migration

### Test/Documentation Files Created:
- `test_document_upload.php` - CLI documentation
- `Service_Request_API.postman_collection.json` - Postman collection
- `public/service-request-form.html` - HTML test form
- `SERVICE_REQUEST_DOCUMENTATION.md` - This documentation

## Next Steps

1. Test the functionality with real image files
2. Implement file deletion when service requests are deleted
3. Add image optimization/resizing if needed
4. Consider adding file download endpoints with authentication
5. Implement file access logging for security audit
