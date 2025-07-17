<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class YearlyReport extends Model
{
    protected $fillable = [
        'year_id',
        'title',
        'description',
        'status',
        'file_url',
        'file_name',
        'file_size',
        'report_date',
        'created_by',
        'published_at'
    ];

    protected $casts = [
        'report_date' => 'date',
        'published_at' => 'datetime',
        'file_size' => 'integer'
    ];

    /**
     * Get the year that owns the yearly report
     */
    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }

    /**
     * Boot method to automatically set report_date and title
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($yearlyReport) {
            if (!$yearlyReport->report_date) {
                $yearlyReport->report_date = $yearlyReport->generateReportDate();
            }
            
            if (!$yearlyReport->title) {
                $yearlyReport->title = $yearlyReport->generateDefaultTitle();
            }
        });

        static::updating(function ($yearlyReport) {
            if ($yearlyReport->isDirty('year_id')) {
                $yearlyReport->report_date = $yearlyReport->generateReportDate();
            }
        });
    }

    /**
     * Generate the report date (December 31st of the year)
     */
    public function generateReportDate(): string
    {
        if ($this->year) {
            return "{$this->year->year_value}-12-31";
        }
        
        // Fallback if relationship is not loaded
        $year = Year::find($this->year_id);
        
        if ($year) {
            return "{$year->year_value}-12-31";
        }

        return Carbon::now()->format('Y-12-31');
    }

    /**
     * Generate default title
     */
    public function generateDefaultTitle(): string
    {
        $year = $this->year ?? Year::find($this->year_id);
        
        if ($year) {
            return "Annual Water Service Report {$year->year_value}";
        }

        return "Annual Water Service Report";
    }

    /**
     * Scope to filter by year
     */
    public function scopeByYear(Builder $query, $year): Builder
    {
        return $query->whereHas('year', function ($q) use ($year) {
            $q->where('year_value', $year);
        });
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus(Builder $query, $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get published reports
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get draft reports
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get reports by date range
     */
    public function scopeByDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    /**
     * Get the formatted report period
     */
    public function getReportPeriodAttribute(): string
    {
        return "Year {$this->year->year_value}";
    }

    /**
     * Get the formatted file size
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if report is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if report is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Publish the report
     */
    public function publish(): bool
    {
        $this->status = 'published';
        $this->published_at = now();
        return $this->save();
    }

    /**
     * Unpublish the report
     */
    public function unpublish(): bool
    {
        $this->status = 'draft';
        $this->published_at = null;
        return $this->save();
    }

    /**
     * Upload file to S3 and update record
     */
    public function uploadFileToS3($file, $customPath = null): bool
    {
        try {
            // Generate file path
            $year = $this->year ?? Year::find($this->year_id);
            
            if ($customPath) {
                $filePath = $customPath;
            } else {
                $fileName = $file->getClientOriginalName();
                $filePath = "yearly_reports/{$year->year_value}/{$fileName}";
            }
            
            // Store file to S3
            $storedPath = Storage::disk('s3')->putFileAs(
                dirname($filePath),
                $file,
                basename($filePath)
            );
            
            if ($storedPath) {
                // Update record with file information
                $this->file_url = Storage::disk('s3')->url($storedPath);
                $this->file_name = $file->getClientOriginalName();
                $this->file_size = $file->getSize();
                
                return $this->save();
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('S3 upload failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload file content to S3 and update record
     */
    public function uploadContentToS3($content, $fileName, $customPath = null): bool
    {
        try {
            // Generate file path
            $year = $this->year ?? Year::find($this->year_id);
            
            if ($customPath) {
                $filePath = $customPath;
            } else {
                $filePath = "yearly_reports/{$year->year_value}/{$fileName}";
            }
            
            // Store content to S3
            $stored = Storage::disk('s3')->put($filePath, $content);
            
            if ($stored) {
                // Update record with file information
                $this->file_url = Storage::disk('s3')->url($filePath);
                $this->file_name = $fileName;
                $this->file_size = strlen($content);
                
                return $this->save();
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('S3 content upload failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete file from S3
     */
    public function deleteFileFromS3(): bool
    {
        try {
            if ($this->file_url) {
                // Extract path from URL
                $parsedUrl = parse_url($this->file_url);
                $path = ltrim($parsedUrl['path'], '/');
                
                // Remove bucket name from path if present
                $bucketName = env('AWS_BUCKET');
                if (strpos($path, $bucketName . '/') === 0) {
                    $path = substr($path, strlen($bucketName) + 1);
                }
                
                $deleted = Storage::disk('s3')->delete($path);
                
                if ($deleted) {
                    $this->file_url = null;
                    $this->file_name = null;
                    $this->file_size = null;
                    
                    return $this->save();
                }
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('S3 delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get related monthly reports for this year
     */
    public function getMonthlyReports()
    {
        return MonthlyReport::where('year_id', $this->year_id)
            ->with('month')
            ->orderBy('month_id')
            ->get();
    }

    /**
     * Get monthly reports completion status for this year
     */
    public function getMonthlyCompletionStatus()
    {
        $monthlyReports = $this->getMonthlyReports();
        $completedMonths = $monthlyReports->count();
        $totalMonths = 12;
        
        return [
            'completed' => $completedMonths,
            'total' => $totalMonths,
            'percentage' => ($completedMonths / $totalMonths) * 100,
            'missing' => $totalMonths - $completedMonths
        ];
    }
}
