# Admin Document Access Guide

## 🔑 Method 1: Web Interface (Recommended)

### Step 1: Open Admin Panel
Visit: `http://localhost:8000/admin-documents.html`

### Step 2: Login with Admin Credentials
- Email: Your admin email
- Password: Your admin password

### Step 3: View Documents
- All service requests will show with their documents
- Click any image to view full-size
- Documents are loaded securely with authentication

## 🛠️ Method 2: API Testing (For Developers)

### Step 1: Get Admin Token
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "your-admin@email.com",
    "password": "your-password"
  }'
```

### Step 2: View Service Requests with Documents
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/admin/service-requests
```

### Step 3: View Individual Request with Documents
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/admin/service-requests/5
```

### Step 4: Access Individual Document Files
```bash
# ID Card Front
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/admin/service-requests/5/documents/id_doc/front.jpg

# ID Card Back  
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/admin/service-requests/5/documents/id_doc/back.jpg

# Family Book Page
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/admin/service-requests/5/documents/family_book/page1.jpg
```

## 🎯 Method 3: Quick Viewer (Simple)

### Create Admin Quick Access
Visit: `http://localhost:8000/quick-viewer.html`
- Enter service request ID
- Login when prompted
- View documents for that specific request

## 📋 Available Admin Endpoints

| Endpoint | Method | Description |
|----------|---------|------------|
| `/api/admin/service-requests` | GET | List all requests with documents |
| `/api/admin/service-requests/{id}` | GET | Single request with documents |
| `/api/admin/service-requests/{id}/documents/{type}/{filename}` | GET | Serve document file |

## 🔐 Document Types & Filenames

### ID Documents
- Type: `id_doc`
- Filenames: `front.jpg`, `back.jpg`

### Family Book Documents
- Type: `family_book` 
- Filenames: `page1.jpg`, `page2.jpg`, etc.

## ⚠️ Important Notes

1. **Authentication Required**: All admin endpoints require a valid Bearer token
2. **HTTPS Recommended**: Use HTTPS in production for security
3. **Access Logging**: Consider logging all document access for audit trails
4. **File Types**: Supports JPG, JPEG, PNG, PDF formats
5. **Error Handling**: Invalid requests return 404 to prevent information leakage
