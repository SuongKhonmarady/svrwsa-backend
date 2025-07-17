# Session Timeout & Token Expiry Implementation

## 🔐 Overview
This implementation provides comprehensive session and token management for your Laravel Sanctum-based API with automatic expiry, refresh capabilities, and security features.

## 🚀 Features Implemented

### ✅ **Enhanced Authentication Controller**
- **Flexible Token Expiry**: Configurable expiry times
- **Remember Me**: Extended sessions for trusted devices  
- **Token Refresh**: Extend token life without re-login
- **Multiple Logout**: Logout from current device or all devices
- **Token Status**: Check remaining time and expiry info
- **Cleanup Utility**: Remove expired tokens

### ✅ **Token Expiry Middleware** 
- **Automatic Expiry Check**: Validates tokens on each request
- **Graceful Expiry**: Clean token removal when expired
- **Expiry Warnings**: Headers notify when token expires soon
- **Last Used Tracking**: Updates token usage timestamps

### ✅ **Configurable Settings**
- **Environment Variables**: Easy configuration via .env
- **Multiple Expiry Options**: Different times for different scenarios
- **Warning Thresholds**: Customizable warning periods

### ✅ **Console Command**
- **Automated Cleanup**: Remove expired and old tokens
- **Dry Run Mode**: Preview before deletion
- **Detailed Reports**: See what will be cleaned

## 📡 API Endpoints

### **Authentication Endpoints**

#### Login with Optional Remember Me
```http
POST /api/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password",
    "remember_me": true  // Optional: extends token life
}
```

**Response:**
```json
{
    "message": "Login successful",
    "token": "1|abc123...",
    "expires_at": "2025-07-15T08:00:00.000000Z",
    "expires_in_minutes": 480,
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com", 
        "role": "admin",
        "role_display": "Administrator"
    }
}
```

#### Check Token Status
```http
GET /api/token-status
Authorization: Bearer {token}
```

**Response:**
```json
{
    "token_name": "auth-token",
    "created_at": "2025-07-14T08:00:00.000000Z",
    "expires_at": "2025-07-15T08:00:00.000000Z", 
    "expires_in_minutes": 240,
    "is_expiring_soon": false,
    "last_used_at": "2025-07-14T12:00:00.000000Z",
    "user": {
        "id": 1,
        "name": "Admin User",
        "role": "admin"
    }
}
```

#### Refresh Token
```http
POST /api/refresh-token
Authorization: Bearer {token}
```

#### Logout from Current Device
```http
POST /api/logout
Authorization: Bearer {token}
```

#### Logout from All Devices
```http
POST /api/logout-all
Authorization: Bearer {token}
```

## ⚙️ Configuration

### **Environment Variables (.env)**
```bash
# Token expiry settings
SANCTUM_TOKEN_EXPIRY_MINUTES=480         # 8 hours
SANCTUM_TOKEN_EXPIRY_HOURS=8             # 8 hours  
SANCTUM_REMEMBER_ME_DAYS=30              # 30 days
SANCTUM_EXPIRY_WARNING_MINUTES=30        # Warning threshold

# Session settings
SESSION_LIFETIME=480                      # 8 hours
SESSION_EXPIRE_ON_CLOSE=false
```

### **Token Expiry Options**
- **Regular Login**: 8 hours (configurable)
- **Remember Me**: 30 days (configurable)  
- **Warning Period**: 30 minutes before expiry
- **Auto Cleanup**: Configurable days for old tokens

## 🔧 Console Commands

### **Clean Expired Tokens**
```bash
# Preview what will be deleted
php artisan tokens:cleanup --dry-run

# Delete expired tokens
php artisan tokens:cleanup

# Delete tokens older than 14 days
php artisan tokens:cleanup --days=14
```

### **Setup Cron Job**
Add to your server's crontab for automatic cleanup:
```bash
# Run daily at 2 AM
0 2 * * * cd /path/to/your/app && php artisan tokens:cleanup --days=7
```

## 🛡️ Security Features

### **Automatic Token Expiry**
- Tokens automatically expire after set time
- Expired tokens are immediately deleted
- Clean error messages for expired tokens

### **Session Tracking**
- Track last used time for each token
- IP address and user agent tracking via sessions table
- Detect inactive tokens

### **Warning System**
- HTTP headers warn of approaching expiry:
  - `X-Token-Expiring-Soon: true`
  - `X-Token-Expires-In: 25` (minutes)
  - `X-Token-Expires-At: 2025-07-15T08:00:00.000000Z`

### **Multiple Session Management**
- Users can logout from all devices
- Force single session by uncommenting token deletion in login
- Track active sessions per user

## 📱 Frontend Integration

### **JavaScript Example**
```javascript
// Check for expiry warnings in response headers
function checkTokenExpiry(response) {
    if (response.headers.get('X-Token-Expiring-Soon') === 'true') {
        const minutesLeft = response.headers.get('X-Token-Expires-In');
        
        // Show warning to user
        showExpiryWarning(`Session expires in ${minutesLeft} minutes`);
        
        // Auto-refresh if needed
        if (minutesLeft <= 10) {
            refreshToken();
        }
    }
}

// Refresh token
async function refreshToken() {
    try {
        const response = await fetch('/api/refresh-token', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${getToken()}`,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Update stored token
            localStorage.setItem('token', data.token);
            console.log('Token refreshed successfully');
        }
    } catch (error) {
        console.error('Token refresh failed:', error);
        // Redirect to login
        window.location.href = '/login';
    }
}
```

## 🔄 Database Updates Needed

### **Add User Tracking to News** (Optional Enhancement)
```sql
ALTER TABLE news 
ADD COLUMN created_by BIGINT UNSIGNED NULL,
ADD COLUMN updated_by BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (created_by) REFERENCES users(id),
ADD FOREIGN KEY (updated_by) REFERENCES users(id);
```

## 📊 Monitoring & Maintenance

### **Token Usage Statistics**
```sql
-- Count active tokens
SELECT COUNT(*) as active_tokens 
FROM personal_access_tokens 
WHERE expires_at > NOW() OR expires_at IS NULL;

-- Count expired tokens
SELECT COUNT(*) as expired_tokens 
FROM personal_access_tokens 
WHERE expires_at < NOW();

-- Tokens by user
SELECT u.name, u.email, COUNT(t.id) as token_count
FROM users u 
LEFT JOIN personal_access_tokens t ON u.id = t.tokenable_id 
WHERE t.expires_at > NOW() OR t.expires_at IS NULL
GROUP BY u.id, u.name, u.email;
```

### **Performance Tips**
- Run token cleanup regularly (daily recommended)
- Monitor token table size
- Consider database indexing on expires_at column
- Set up monitoring for excessive token creation

## ✅ Implementation Checklist

- [x] Enhanced AuthController with token management
- [x] TokenExpiryMiddleware for automatic checking
- [x] Console command for token cleanup
- [x] Updated routes with new endpoints
- [x] Middleware registration in bootstrap
- [x] Configuration options in sanctum.php
- [x] Environment variables documentation
- [ ] Add to crontab for automatic cleanup
- [ ] Update frontend to handle expiry warnings
- [ ] Test all expiry scenarios
- [ ] Monitor token usage in production

This implementation provides a robust, secure, and user-friendly session management system for your Laravel API! 🚀
