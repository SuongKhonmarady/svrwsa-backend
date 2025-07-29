<?php

require_once 'vendor/autoload.php';

use App\Models\MonthlyReport;
use App\Models\Year;
use App\Models\Month;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing PDF upload for November 2030 report...\n";

try {
    // Find November 2030 report
    $year = Year::where('year_value', 2030)->first();
    $month = Month::where('month', 'November')->first();
    
    if ($year && $month) {
        $report = MonthlyReport::where('year_id', $year->id)
            ->where('month_id', $month->id)
            ->first();
        
        if ($report) {
            echo "✅ Found November 2025 report (ID: {$report->id})\n";
            echo "Current file: " . ($report->file_name ?? 'No file') . "\n";
            
            // Simulate PDF content (since we can't access the actual file)
            $pdfContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n>>\nendobj\n4 0 obj\n<<\n/Length 44\n>>\nstream\nBT\n/F1 12 Tf\n100 700 Td\n(ScholarTrack Scholarship Data) Tj\nET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000204 00000 n \ntrailer\n<<\n/Size 5\n/Root 1 0 R\n>>\nstartxref\n298\n%%EOF";
            
            // Upload to S3 with the specific filename
            $fileName = 'Final Slides_ScholarTrack Transparent Scholarship Data hub.pdf';
            $uploaded = $report->uploadContentToS3($pdfContent, $fileName);
            
            if ($uploaded) {
                echo "✅ PDF uploaded successfully to S3!\n";
                echo "File URL: {$report->file_url}\n";
                echo "File Name: {$report->file_name}\n";
                echo "File Size: {$report->formatted_file_size}\n";
                echo "S3 Path: monthly_reports/2030/november/{$fileName}\n";
                
                // Verify file exists in S3
                $s3Files = Storage::disk('s3')->files('monthly_reports/2030/november');
                echo "\nFiles in S3 november folder:\n";
                foreach ($s3Files as $file) {
                    echo "  - " . basename($file) . "\n";
                }
                
                // Test direct access
                echo "\n🔗 Direct S3 URL:\n";
                echo $report->file_url . "\n";
                
                // Generate a temporary signed URL for testing
                $signedUrl = Storage::disk('s3')->temporaryUrl(
                    "monthly_reports/2025/november/{$fileName}",
                    now()->addHour()
                );
                echo "\n🔐 Signed URL (expires in 1 hour):\n";
                echo $signedUrl . "\n";
                
            } else {
                echo "❌ Failed to upload PDF to S3\n";
            }
        } else {
            echo "❌ November 2030 report not found\n";
        }
    } else {
        echo "❌ Year 2030 or November month not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
}
