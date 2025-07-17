PUBLIC ROUTES (No Authentication)
    GET /api/reports/years                    # All years
    GET /api/reports/months                   # All months  
    GET /api/reports/monthly                  # Published monthly reports only
    GET /api/reports/yearly                   # Published yearly reports only
    GET /api/reports/analytics/overview       # Basic public stats
STAFF ROUTES (Authentication Required)
    GET /api/reports/staff/monthly/all        # All reports (including drafts)
    GET /api/reports/staff/yearly/all         # All reports (including drafts)
    POST /api/reports/staff/monthly/{id}/publish
    GET /api/reports/staff/analytics/dashboard
ADMIN ROUTES (Admin Role Required)
    POST /api/reports/admin/monthly           # Create reports
    PUT /api/reports/admin/monthly/{id}       # Update reports
    DELETE /api/reports/admin/monthly/{id}    # Delete reports