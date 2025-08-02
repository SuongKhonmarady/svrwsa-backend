/**
 * SVR WSA Reports API Client
 * A comprehensive JavaScript client for managing reports with PDF uploads
 */

class ReportsAPI {
    constructor(baseUrl = 'http://localhost:8000/api') {
        this.baseUrl = baseUrl;
        this.token = localStorage.getItem('authToken');
        this.onTokenExpired = null;
        console.log('API initialized with base URL:', this.baseUrl);
    }

    // Set authentication token
    setToken(token) {
        this.token = token;
        localStorage.setItem('authToken', token);
    }

    // Clear authentication token
    clearToken() {
        this.token = null;
        localStorage.removeItem('authToken');
    }

    // Get default headers
    getHeaders(includeContentType = true) {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        if (this.token) {
            headers.Authorization = `Bearer ${this.token}`;
        }

        if (includeContentType) {
            headers['Content-Type'] = 'application/json';
        }

        return headers;
    }

    // Handle API response
    async handleResponse(response) {
        if (response.status === 401) {
            this.clearToken();
            if (this.onTokenExpired) {
                this.onTokenExpired();
            }
            throw new Error('Authentication required');
        }

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }

        return data;
    }

    // Authentication Methods
    async login(email, password) {
        const response = await fetch(`${this.baseUrl}/login`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify({ email, password })
        });

        const data = await this.handleResponse(response);

        if (data.token) {
            this.setToken(data.token);
        }

        return data;
    }

    async logout() {
        if (!this.token) return;

        try {
            await fetch(`${this.baseUrl}/logout`, {
                method: 'POST',
                headers: this.getHeaders()
            });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.clearToken();
        }
    }

    async getUserInfo() {
        const response = await fetch(`${this.baseUrl}/user`, {
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    async getTokenStatus() {
        const response = await fetch(`${this.baseUrl}/token-status`, {
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    // Reports Management Methods
    async getReports(includeAll = false) {
        try {
            console.log('Fetching reports...');
            const endpoint = includeAll ? '/reports/staff/monthly/all' : '/reports/monthly';
            console.log('Using endpoint:', `${this.baseUrl}${endpoint}`);
            console.log('Request headers:', this.getHeaders());
            
            const response = await fetch(`${this.baseUrl}${endpoint}`, {
                method: 'GET',
                headers: this.getHeaders()
            });

            console.log('Raw response:', response);
            const data = await this.handleResponse(response);
            console.log('Reports data received:', data);
            console.log('Reports array:', data.data);
            return data;
        } catch (error) {
            console.error('Error fetching reports:', error);
            throw new Error(`Failed to fetch reports: ${error.message}`);
        }
    }

    async getReport(id) {
        const response = await fetch(`${this.baseUrl}/reports/monthly/${id}`, {
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    async createReport(reportData, file = null) {
        const formData = new FormData();

        // Add report data
        Object.keys(reportData).forEach(key => {
            if (reportData[key] !== null && reportData[key] !== undefined) {
                formData.append(key, reportData[key]);
            }
        });

        // Add file if provided
        if (file) {
            formData.append('file', file);
        }

        const response = await fetch(`${this.baseUrl}/reports/admin/monthly`, {
            method: 'POST',
            headers: this.getHeaders(false), // Don't include Content-Type for FormData
            body: formData
        });

        return await this.handleResponse(response);
    }

    async updateReport(id, reportData, file = null) {
        const formData = new FormData();

        // Add report data
        Object.keys(reportData).forEach(key => {
            if (reportData[key] !== null && reportData[key] !== undefined) {
                formData.append(key, reportData[key]);
            }
        });

        // Add file if provided
        if (file) {
            formData.append('file', file);
        }

        const response = await fetch(`${this.baseUrl}/reports/admin/monthly/${id}`, {
            method: 'PUT',
            headers: this.getHeaders(false), // Don't include Content-Type for FormData
            body: formData
        });

        return await this.handleResponse(response);
    }

    async deleteReport(id) {
        const response = await fetch(`${this.baseUrl}/reports/admin/monthly/${id}`, {
            method: 'DELETE',
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    async publishReport(id) {
        const response = await fetch(`${this.baseUrl}/reports/staff/monthly/${id}/publish`, {
            method: 'POST',
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    async unpublishReport(id) {
        const response = await fetch(`${this.baseUrl}/reports/staff/monthly/${id}/unpublish`, {
            method: 'POST',
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    // Reference Data Methods
    async getYears() {
        const response = await fetch(`${this.baseUrl}/reports/years`, {
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    async getMonths() {
        try {
            console.log('Fetching months from:', `${this.baseUrl}/reports/months`);
            const response = await fetch(`${this.baseUrl}/reports/months`, {
                method: 'GET',
                headers: this.getHeaders()
            });

            const data = await this.handleResponse(response);
            console.log('Months response:', data);
            return data;
        } catch (error) {
            console.error('Error fetching months:', error);
            throw new Error(`Failed to fetch months: ${error.message}`);
        }
    }

    async getCurrentYear() {
        const response = await fetch(`${this.baseUrl}/reports/years/current`, {
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    // Analytics Methods
    async getAnalyticsDashboard() {
        const response = await fetch(`${this.baseUrl}/reports/staff/analytics/dashboard`, {
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    async getMissingReports() {
        const response = await fetch(`${this.baseUrl}/reports/staff/analytics/missing`, {
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    async getCompletionRates() {
        const response = await fetch(`${this.baseUrl}/reports/staff/analytics/completion`, {
            headers: this.getHeaders()
        });

        return await this.handleResponse(response);
    }

    // Utility Methods
    validateFile(file, maxSizeInMB = 10) {
        const allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];

        if (!allowedTypes.includes(file.type)) {
            throw new Error('Invalid file type. Please select a PDF, DOC, DOCX, or TXT file.');
        }

        if (file.size > maxSizeInMB * 1024 * 1024) {
            throw new Error(`File too large. Maximum size is ${maxSizeInMB}MB.`);
        }

        return true;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    formatDateTime(dateString) {
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Progress tracking for uploads
    async uploadWithProgress(url, formData, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && onProgress) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    onProgress(percentComplete);
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (error) {
                        reject(new Error('Invalid JSON response'));
                    }
                } else {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        reject(new Error(response.message || `HTTP error! status: ${xhr.status}`));
                    } catch (error) {
                        reject(new Error(`HTTP error! status: ${xhr.status}`));
                    }
                }
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Network error'));
            });

            xhr.open('POST', url);
            xhr.setRequestHeader('Authorization', `Bearer ${this.token}`);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.send(formData);
        });
    }

    // Batch operations
    async batchPublish(reportIds) {
        const results = [];

        for (const id of reportIds) {
            try {
                const result = await this.publishReport(id);
                results.push({ id, success: true, result });
            } catch (error) {
                results.push({ id, success: false, error: error.message });
            }
        }

        return results;
    }

    async batchUnpublish(reportIds) {
        const results = [];

        for (const id of reportIds) {
            try {
                const result = await this.unpublishReport(id);
                results.push({ id, success: true, result });
            } catch (error) {
                results.push({ id, success: false, error: error.message });
            }
        }

        return results;
    }

    async batchDelete(reportIds) {
        const results = [];

        for (const id of reportIds) {
            try {
                const result = await this.deleteReport(id);
                results.push({ id, success: true, result });
            } catch (error) {
                results.push({ id, success: false, error: error.message });
            }
        }

        return results;
    }
}

// Export for different environments
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReportsAPI;
} else if (typeof window !== 'undefined') {
    window.ReportsAPI = ReportsAPI;
}
