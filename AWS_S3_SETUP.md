# AWS S3 Setup for File Storage

This application has been configured to use AWS S3 for storing report files instead of local storage.

## Configuration

### 1. Environment Variables

Make sure the following environment variables are set in your `.env` file:

```bash
# Default filesystem disk (set to s3 for S3 storage)
FILESYSTEM_DISK=s3

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### 2. AWS IAM Permissions

Your AWS IAM user/role needs the following permissions for the S3 bucket:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:GetObjectAcl",
                "s3:PutObjectAcl"
            ],
            "Resource": "arn:aws:s3:::your-bucket-name/*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "s3:ListBucket"
            ],
            "Resource": "arn:aws:s3:::your-bucket-name"
        }
    ]
}
```

### 3. S3 Bucket Configuration

1. Create an S3 bucket in your AWS account
2. Configure the bucket for public read access (if you want files to be directly accessible via URL)
3. Set up CORS policy if needed for web access:

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
        "AllowedOrigins": ["*"],
        "ExposeHeaders": []
    }
]
```

## File Structure in S3

Files will be stored with the following structure:

### Monthly Reports
```
monthly_reports/
├── 2024/
│   ├── january/
│   ├── february/
│   └── ...
└── 2025/
    ├── january/
    └── ...
```

### Yearly Reports
```
yearly_reports/
├── 2024/
│   └── annual_report_2024.pdf
└── 2025/
    └── annual_report_2025.pdf
```

## Features

### Automatic File Management
- Files are automatically uploaded to S3 when creating/updating reports
- Old files are automatically deleted when new ones are uploaded
- Files are deleted from S3 when reports are deleted

### File URL Generation
- Public URLs are automatically generated for uploaded files
- URLs are stored in the database for easy access

### Error Handling
- Upload failures are properly handled
- Failed uploads prevent report creation/update
- Detailed error logging for troubleshooting

## Migration from Local Storage

If you're migrating from local storage to S3:

1. Update your environment variables
2. Upload existing files to S3 manually or create a migration script
3. Update file URLs in the database to point to S3
4. Test the upload/download functionality

## Testing

To test the S3 integration:

1. Create a new monthly or yearly report with a file
2. Verify the file appears in your S3 bucket
3. Check that the file URL in the database points to S3
4. Update the report with a new file and verify the old file is deleted
5. Delete the report and verify the file is removed from S3

## Troubleshooting

### Common Issues

1. **403 Forbidden Error**: Check IAM permissions
2. **File not uploading**: Verify AWS credentials and bucket name
3. **Files not accessible**: Check bucket public access settings
4. **Region errors**: Ensure AWS_DEFAULT_REGION matches your bucket region

### Logs

Check Laravel logs for detailed error messages:
```bash
tail -f storage/logs/laravel.log
```

S3 operations are logged with the prefix "S3 upload failed:" or "S3 delete failed:"
