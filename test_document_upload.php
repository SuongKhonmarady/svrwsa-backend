<?php
/**
 * Test script for document upload functionality
 * This script demonstrates how the API should be called with file uploads
 */

// Sample API endpoint test
$apiUrl = 'http://localhost:8000/api/service-requests';

// Sample form data structure
$formData = [
    'name' => 'Suong Khonmarady',
    'email' => 'test@gmail.com',
    'phone' => '0987654321',
    'address' => 'Phnom Penh',
    'service_type' => 'Water Supply',
    'details' => 'Need new water connection',
    // Files should be uploaded as:
    // id_card[] = front_image_file
    // id_card[] = back_image_file
    // family_book[] = page1_file
    // family_book[] = page2_file (optional, can have multiple)
];

echo "=== Service Request Document Upload API Test Structure ===\n\n";
echo "Endpoint: POST {$apiUrl}\n\n";
echo "Form Data Structure:\n";
foreach ($formData as $key => $value) {
    echo "- {$key}: {$value}\n";
}

echo "\nFile Upload Fields:\n";
echo "- id_card[]: Front image (JPG/JPEG/PNG, max 2MB)\n";
echo "- id_card[]: Back image (JPG/JPEG/PNG, max 2MB)\n";
echo "- family_book[]: Page 1 (JPG/JPEG/PNG/PDF, max 2MB)\n";
echo "- family_book[]: Page 2+ (optional, multiple files allowed)\n\n";

echo "Expected Response Format:\n";
$expectedResponse = [
    'success' => true,
    'data' => [
        'id' => 3,
        'name' => 'Suong Khonmarady',
        'email' => 'test@gmail.com',
        'phone' => '0987654321',
        'address' => 'Phnom Penh',
        'service_type' => 'Water Supply',
        'details' => 'Need new water connection',
        'id_card' => [
            'https://yourdomain.com/storage/id_docs/3/front.jpg',
            'https://yourdomain.com/storage/id_docs/3/back.jpg'
        ],
        'family_book' => [
            'https://yourdomain.com/storage/family_books/3/page1.jpg',
            'https://yourdomain.com/storage/family_books/3/page2.jpg'
        ],
        'created_at' => '2025-08-01T10:02:30.000000Z',
        'status' => [
            'id' => 9,
            'name' => 'Pending'
        ]
    ],
    'message' => 'Request submitted successfully with documents!'
];

echo json_encode($expectedResponse, JSON_PRETTY_PRINT);
echo "\n\n";

echo "=== cURL Example ===\n";
echo "curl -X POST {$apiUrl} \\\n";
echo "  -F 'name=Suong Khonmarady' \\\n";
echo "  -F 'email=test@gmail.com' \\\n";
echo "  -F 'phone=0987654321' \\\n";
echo "  -F 'address=Phnom Penh' \\\n";
echo "  -F 'service_type=Water Supply' \\\n";
echo "  -F 'details=Need new water connection' \\\n";
echo "  -F 'id_card[]=@/path/to/front.jpg' \\\n";
echo "  -F 'id_card[]=@/path/to/back.jpg' \\\n";
echo "  -F 'family_book[]=@/path/to/page1.jpg' \\\n";
echo "  -F 'family_book[]=@/path/to/page2.jpg'\n\n";

echo "=== Validation Rules ===\n";
echo "- name: required, string, max 255 characters\n";
echo "- email: nullable, valid email format\n";
echo "- phone: nullable, string, max 20 characters\n";
echo "- address: nullable, string, max 500 characters\n";
echo "- service_type: required, string, max 255 characters\n";
echo "- details: nullable, string, max 2000 characters\n";
echo "- id_card: required, array, exactly 2 files\n";
echo "- id_card.*: file, mimes:jpg,jpeg,png, max 2MB\n";
echo "- family_book: required, array, minimum 1 file\n";
echo "- family_book.*: file, mimes:jpg,jpeg,png,pdf, max 2MB\n\n";

echo "=== File Storage Structure ===\n";
echo "storage/app/public/id_docs/{service_request_id}/\n";
echo "├── front.jpg\n";
echo "└── back.jpg\n\n";
echo "storage/app/public/family_books/{service_request_id}/\n";
echo "├── page1.jpg\n";
echo "├── page2.jpg\n";
echo "└── page3.pdf (if more pages)\n\n";

echo "Files will be accessible via:\n";
echo "http://yourdomain.com/storage/id_docs/{id}/front.jpg\n";
echo "http://yourdomain.com/storage/family_books/{id}/page1.jpg\n";
?>
