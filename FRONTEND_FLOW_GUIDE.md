# Frontend Flow for Reports Management with PDF Upload

## 📋 Overview

This document outlines the complete frontend flow for managing monthly reports with PDF file uploads in the SVR WSA system. This guide assumes you're already authenticated and focuses on the reports management functionality.

## 🎯 Key Features

- **CRUD Operations**: Create, Read, Update, Delete reports
- **File Upload**: PDF, DOC, DOCX, TXT file support with validation
- **Status Management**: Draft/Published status toggle
- **Real-time Updates**: Progress tracking and status updates
- **Responsive Design**: Mobile-friendly interface
- **Error Handling**: Comprehensive error management

## � Prerequisites

- User is already authenticated
- Authentication token is available (stored in localStorage or variable)
- Admin/Staff permissions for report management

## 📊 Reports Management Flow

### 1. Load Reports List
```javascript
async function loadReports() {
    const response = await fetch('/api/reports/staff/monthly/all', {
        headers: {
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
        }
    });
    
    const data = await response.json();
    displayReports(data.data);
}
```

### 2. Display Reports
- Show reports in cards/table format
- Display status badges (Draft/Published)
- Show file information if available
- Include action buttons (Edit, Delete, Publish/Unpublish)

## ➕ Create Report Flow

### 1. Form Fields
- **Year**: Dropdown selection (2023, 2024, 2025)
- **Month**: Dropdown selection (January - December)
- **Title**: Text input (required)
- **Description**: Textarea (optional)
- **Status**: Draft/Published selection
- **Created By**: Text input (pre-filled)
- **File**: File upload (PDF, DOC, DOCX, TXT)

### 2. File Upload Validation
```javascript
function validateFile(file) {
    const allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];
    
    if (!allowedTypes.includes(file.type)) {
        throw new Error('Invalid file type');
    }
    
    if (file.size > 10 * 1024 * 1024) { // 10MB limit
        throw new Error('File too large');
    }
}
```

### 3. Form Submission
```javascript
async function createReport(formData) {
    const response = await fetch('/api/admin/reports/monthly', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
        },
        body: formData // FormData object with file
    });
    
    return await response.json();
}
```

## ✏️ Edit Report Flow

### 1. Load Existing Data
```javascript
async function loadReportForEdit(id) {
    const response = await fetch(`/api/reports/monthly/${id}`, {
        headers: {
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
        }
    });
    
    const data = await response.json();
    populateForm(data.data);
}
```

### 2. Form Pre-population
- Fill all form fields with existing data
- Show current file information
- Allow file replacement (optional)
- Maintain form validation

### 3. Update Submission
```javascript
async function updateReport(id, formData) {
    const response = await fetch(`/api/admin/reports/monthly/${id}`, {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
        },
        body: formData
    });
    
    return await response.json();
}
```

## 🗑️ Delete Report Flow

### 1. Confirmation Dialog
```javascript
function deleteReport(id) {
    if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        performDelete(id);
    }
}
```

### 2. Delete Operation
```javascript
async function performDelete(id) {
    const response = await fetch(`/api/admin/reports/monthly/${id}`, {
        method: 'DELETE',
        headers: {
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
        }
    });
    
    if (response.ok) {
        removeReportFromList(id);
        showSuccessMessage('Report deleted successfully');
    }
}
```

## 📤 Publish/Unpublish Flow

### 1. Publish Report
```javascript
async function publishReport(id) {
    const response = await fetch(`/api/reports/staff/monthly/${id}/publish`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
        }
    });
    
    if (response.ok) {
        updateReportStatus(id, 'published');
    }
}
```

### 2. Unpublish Report
```javascript
async function unpublishReport(id) {
    const response = await fetch(`/api/reports/staff/monthly/${id}/unpublish`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
        }
    });
    
    if (response.ok) {
        updateReportStatus(id, 'draft');
    }
}
```

## 📁 File Upload Flow

### 1. File Selection
```javascript
function handleFileSelect(input) {
    const file = input.files[0];
    if (file) {
        try {
            validateFile(file);
            showFileInfo(file);
            selectedFile = file;
        } catch (error) {
            showError(error.message);
            input.value = '';
        }
    }
}
```

### 2. File Information Display
```javascript
function showFileInfo(file) {
    const fileInfo = document.getElementById('fileInfo');
    fileInfo.innerHTML = `
        <strong>Selected file:</strong> ${file.name}<br>
        <strong>Size:</strong> ${formatFileSize(file.size)}<br>
        <strong>Type:</strong> ${file.type}
    `;
    fileInfo.style.display = 'block';
}
```

### 3. Upload Progress (Optional)
```javascript
function uploadWithProgress(formData, onProgress) {
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            onProgress(percentComplete);
        }
    });
    
    xhr.open('POST', '/api/admin/reports/monthly');
    xhr.setRequestHeader('Authorization', `Bearer ${authToken}`);
    xhr.send(formData);
}
```

## 🎨 UI/UX Patterns

### 1. Modal Dialog
```html
<div id="reportModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Create New Report</h2>
        <form id="reportForm">
            <!-- Form fields -->
        </form>
    </div>
</div>
```

### 2. Form Layout
```html
<div class="form-row">
    <div class="form-col">
        <label for="yearId">Year:</label>
        <select id="yearId" required>
            <option value="">Select Year</option>
            <option value="12">2025</option>
        </select>
    </div>
    <div class="form-col">
        <label for="monthId">Month:</label>
        <select id="monthId" required>
            <option value="">Select Month</option>
            <option value="1">January</option>
        </select>
    </div>
</div>
```

### 3. File Upload Area
```html
<div class="file-upload" onclick="document.getElementById('file').click()">
    <input type="file" id="file" accept=".pdf,.doc,.docx,.txt">
    <div id="fileUploadText">
        📁 Click to select PDF file or drag & drop here<br>
        <small>Supported formats: PDF, DOC, DOCX, TXT (Max: 10MB)</small>
    </div>
</div>
```

### 4. Status Badges
```html
<span class="status-badge status-published">Published</span>
<span class="status-badge status-draft">Draft</span>
```

## 🔧 Error Handling

### 1. Network Errors
```javascript
try {
    const response = await fetch(url, options);
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.message || 'Network error');
    }
    
    return data;
} catch (error) {
    showError('Network error: ' + error.message);
}
```

### 2. Validation Errors
```javascript
function validateForm() {
    const errors = [];
    
    if (!document.getElementById('yearId').value) {
        errors.push('Year is required');
    }
    
    if (!document.getElementById('monthId').value) {
        errors.push('Month is required');
    }
    
    if (!document.getElementById('title').value) {
        errors.push('Title is required');
    }
    
    if (errors.length > 0) {
        showError(errors.join('<br>'));
        return false;
    }
    
    return true;
}
```

### 3. Message Display
```javascript
function showMessage(message, type) {
    const messageDiv = document.getElementById('messageArea');
    messageDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    
    if (type !== 'loading') {
        setTimeout(() => messageDiv.innerHTML = '', 5000);
    }
}
```

## 📱 Responsive Design

### 1. Mobile-First CSS
```css
/* Mobile styles */
.form-row {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Desktop styles */
@media (min-width: 768px) {
    .form-row {
        flex-direction: row;
    }
}
```

### 2. Touch-Friendly Elements
```css
.btn {
    padding: 12px 24px;
    min-height: 44px;
    cursor: pointer;
    touch-action: manipulation;
}

.file-upload {
    min-height: 80px;
    padding: 20px;
    cursor: pointer;
}
```

## 🚀 Implementation Steps

### 1. Setup Phase
1. Ensure authentication token is available
2. Create HTML structure for reports management
3. Add CSS styling for responsive design
4. Set up API configuration

### 2. Reports Management Phase
1. Create reports list view
2. Implement create functionality
3. Add edit capabilities
4. Include delete operations

### 3. File Upload Phase
1. Add file selection UI
2. Implement validation
3. Handle upload progress
4. Show file information

### 4. Status Management Phase
1. Add publish/unpublish buttons
2. Update UI based on status
3. Handle status changes
4. Show appropriate badges

### 5. Polish Phase
1. Add loading states
2. Improve error messages
3. Test responsive design
4. Add accessibility features

## 🧪 Testing Checklist

### Functional Testing
- [ ] Load existing reports list
- [ ] Create report without file
- [ ] Create report with file
- [ ] Edit existing report
- [ ] Delete report (with confirmation)
- [ ] Publish/unpublish reports
- [ ] File upload validation
- [ ] Handle API errors gracefully

### UI/UX Testing
- [ ] Responsive design on mobile
- [ ] Modal functionality
- [ ] Form validation feedback
- [ ] Loading states
- [ ] Error messages
- [ ] Success notifications
- [ ] File drag & drop (if implemented)

### Security Testing
- [ ] File type validation
- [ ] File size validation
- [ ] XSS prevention in form inputs
- [ ] Proper API error handling

## 📚 Dependencies

### Required Libraries
- Modern browser with fetch API support
- No external JavaScript libraries required
- CSS Grid/Flexbox support
- Local/Session storage support

### Optional Enhancements
- Drag & drop file upload library
- Progress bar library
- Date picker component
- Rich text editor for descriptions
- Image preview for PDF files

## 🔗 API Integration

### Base Configuration
```javascript
const API_CONFIG = {
    baseUrl: 'http://localhost:8000/api',
    timeout: 30000,
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
};
```

### Authentication Headers
```javascript
function getAuthHeaders() {
    const token = localStorage.getItem('authToken'); // or get from your auth system
    return {
        ...API_CONFIG.headers,
        'Authorization': `Bearer ${token}`
    };
}
```

## 🎬 Quick Start Example

Here's a complete minimal example to get you started:

```javascript
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const authToken = localStorage.getItem('authToken'); // Your existing token
    if (authToken) {
        loadReports();
    }
});

// Main functions
async function loadReports() {
    try {
        const response = await fetch('/api/reports/staff/monthly/all', {
            headers: getAuthHeaders()
        });
        
        const data = await response.json();
        displayReports(data.data);
    } catch (error) {
        showError('Failed to load reports: ' + error.message);
    }
}

function displayReports(reports) {
    const container = document.getElementById('reports-container');
    container.innerHTML = reports.map(report => `
        <div class="report-card">
            <h3>${report.title}</h3>
            <p>Status: ${report.status}</p>
            <div class="actions">
                <button onclick="editReport(${report.id})">Edit</button>
                <button onclick="deleteReport(${report.id})">Delete</button>
            </div>
        </div>
    `).join('');
}
```

This streamlined frontend flow provides a complete solution for managing reports with PDF uploads, focusing on the core functionality you need after authentication is already handled.
