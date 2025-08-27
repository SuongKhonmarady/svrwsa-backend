**Note:** Both `/api/admin/service-requests` and `/api/admin/service-requests/by-status` use the same method and provide identical functionality.

## Example Usage

### Using cURL
```bash
# Get ALL service requests (public)
curl "http://your-domain.com/api/service-requests/by-status"

# Get all pending service requests (public)
curl "http://your-domain.com/api/service-requests/by-status?status=Pending"

# Get all completed service requests (admin)
curl -H "Authorization: Bearer {admin_token}" \
     "http://your-domain.com/api/admin/service-requests?status=Completed"

# Get ALL service requests (admin)
curl -H "Authorization: Bearer {admin_token}" \
     "http://your-domain.com/api/admin/service-requests"

# Alternative admin endpoint (same functionality)
curl -H "Authorization: Bearer {admin_token}" \
     "http://your-domain.com/api/admin/service-requests/by-status?status=Pending"
```

### Using JavaScript/Fetch
```javascript
// Get all service requests (public)
const response = await fetch('/api/service-requests/by-status');
const data = await response.json();

// Get pending service requests (public)
const responsePending = await fetch('/api/service-requests/by-status?status=Pending');
const dataPending = await responsePending.json();

// Get completed service requests (admin)
const responseCompleted = await fetch('/api/admin/service-requests?status=Completed', {
    headers: { 'Authorization': 'Bearer ' + adminToken }
});
const dataCompleted = await responseCompleted.json();

console.log(`Found ${data.count} total requests`);
console.log(`Found ${dataPending.count} pending requests`);
console.log(`Found ${dataCompleted.count} completed requests`);
```

### Using Postman
1. Set method to `GET`
2. Set URL to `{{base_url}}/api/service-requests/by-status` (for all requests)
3. Set URL to `{{base_url}}/api/service-requests/by-status?status=Pending` (for filtered requests)
4. For admin endpoints, add Authorization header with Bearer token
5. Admin endpoints support both base path and `/by-status` path

## Error Handling

**Invalid Status:**
```json
{
    "message": "The selected status is invalid.",
    "errors": {
        "status": ["The selected status is invalid."]
    }
}
```

## Related Endpoints

- `GET /api/service-requests` - Get all service requests (currently commented out)
- `POST /api/service-requests` - Create new service request
- `PATCH /api/service-requests/{id}/status` - Update service request status
- `GET /api/statuses` - Get all available statuses with their IDs
- `GET /api/admin/service-requests` - Admin: Get all or filtered service requests with documents
- `GET /api/admin/service-requests/by-status` - Admin: Alternative path (same functionality)

## Notes

- Status names are case-sensitive
- The `status` parameter is now optional
- When no status is provided, returns ALL service requests
- The response includes a count of results (filtered or total)
- All relationships (commune, district, province, occupation, usage_type) are loaded
- Admin endpoints provide access to sensitive documents
- Public endpoints hide sensitive information for security
- This single endpoint now replaces the need for separate "all" and "filtered" endpoints
- Use `/api/statuses` to get the list of all available statuses for frontend dropdowns
- Admin can use either `/api/admin/service-requests` or `/api/admin/service-requests/by-status` - both work identically
