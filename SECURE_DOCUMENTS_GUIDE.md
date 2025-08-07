# Secure Service Request System - Privacy Protected

## 🔒 Security Overview
The service request system has been enhanced with privacy protection for sensitive customer documents:

- **ID Cards** and **Family Books** are now stored in private storage
- Documents are only accessible to authenticated administrators
- Public API endpoints no longer expose document URLs
- Secure document serving with authentication required

## 📁 Storage Structure

### Private Storage (Secure)
```
storage/app/private/
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

### Database Storage
Documents paths are stored as private file paths (not public URLs):
```json
{
  "id_card": [
    "id_docs/5/front.jpg",
    "id_docs/5/back.jpg"
  ],
  "family_book": [
    "family_books/5/page1.jpg"
  ]
}
```

## 🌐 API Endpoints

### Public Endpoints (Documents Hidden)
- `GET /api/service-requests` - List requests (no documents)
- `GET /api/service-requests/{id}` - Single request (no documents)
- `POST /api/service-requests` - Submit new request with documents

### Admin-Only Endpoints (Authentication Required)
- `GET /api/admin/service-requests` - List requests with documents
- `GET /api/admin/service-requests/{id}` - Single request with documents
- `GET /api/admin/service-requests/{id}/documents/{type}/{filename}` - Serve document file

### Document Serving
Documents are served through a secure endpoint that:
1. Verifies admin authentication
2. Validates request ID exists
3. Checks document type is valid
4. Serves file with proper headers

## 🔑 Authentication Required

Admin authentication is required for:
- Viewing service requests with documents
- Accessing individual document files
- Managing service request status

## 🖥️ User Interfaces

### Public Interface
- **URL**: `http://localhost:8000/view-documents.html`
- **Access**: Public (no authentication)
- **Features**: View service requests without sensitive documents
- **Privacy**: Documents are hidden with privacy notice

### Admin Interface  
- **URL**: `http://localhost:8000/admin-documents.html`
- **Access**: Admin authentication required
- **Features**: Full access to all service requests and documents
- **Security**: Token-based authentication, secure document loading

## 🛡️ Privacy Features

### Document Protection
- Documents stored outside web-accessible directory
- No direct file URLs exposed
- Authentication required for all document access
- Error handling prevents information leakage

### Public API Response (Documents Hidden)
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "Suong Khonmarady",
    "email": "mara@gmail.com",
    "phone": "12323618271",
    "address": "Phnom Penh",
    "service_type": "Water Supply",
    "details": null,
    "created_at": "2025-08-03T09:05:54.000000Z",
    "updated_at": "2025-08-03T09:05:54.000000Z",
    "status": {
      "id": 1,
      "name": "Pending"
    }
    // Note: id_card and family_book fields are hidden
  }
}
```

### Admin API Response (Documents Visible)
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "Suong Khonmarady",
    "email": "mara@gmail.com",
    "phone": "12323618271",
    "address": "Phnom Penh", 
    "service_type": "Water Supply",
    "details": null,
    "id_card": [
      "id_docs/5/front.jpg",
      "id_docs/5/back.jpg"
    ],
    "family_book": [
      "family_books/5/page1.jpg"
    ],
    "created_at": "2025-08-03T09:05:54.000000Z",
    "updated_at": "2025-08-03T09:05:54.000000Z",
    "status": {
      "id": 1,
      "name": "Pending"
    }
  }
}
```

## 🔧 Implementation Details

### Controller Changes
- Private storage paths instead of public URLs
- Document serving with authentication
- Hidden fields for non-admin users
- Secure file handling

### Security Measures
- File type validation maintained
- Size limits enforced
- Path traversal protection
- Authentication verification
- Error handling without information disclosure

## 📝 Testing

### Public Access Test
```bash
# This will show requests without documents
curl http://localhost:8000/api/service-requests
```

### Admin Access Test
```bash
# Login first
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Get token from response, then:
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/admin/service-requests
```

### Document Access Test
```bash
# Only works with admin token
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/admin/service-requests/5/documents/id_doc/front.jpg
```

## ⚠️ Privacy Compliance

This implementation ensures:
- **Data Minimization**: Public APIs only show necessary information
- **Access Control**: Sensitive documents require authorization
- **Secure Storage**: Documents not web-accessible
- **Audit Trail**: All document access can be logged
- **User Privacy**: Customer documents protected from unauthorized access

## 🚀 Deployment Notes

When deploying to production:
1. Ensure `storage/app/private/` has proper permissions
2. Configure admin user authentication
3. Use HTTPS for all admin operations
4. Consider additional logging for document access
5. Regular backup of private storage directory

## 📞 Support

For privacy-related questions or security concerns, contact the system administrator.
