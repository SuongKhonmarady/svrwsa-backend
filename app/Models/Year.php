<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Year extends Model
{
    protected $fillable = [
        'year_value'
    ];

    protected $casts = [
        'year_value' => 'integer'
    ];

    /**
     * Get all monthly reports for this year
     */
    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    /**
     * Get the yearly report for this year
     */
    public function yearlyReport(): HasMany
    {
        return $this->hasMany(YearlyReport::class);
    }

    /**
     * Get the year value as string
     */
    public function getYearAttribute(): string
    {
        return (string) $this->year_value;
    }

    /**
     * Scope to get years within a range
     */
    public function scopeWithinRange($query, $startYear, $endYear)
    {
        return $query->whereBetween('year_value', [$startYear, $endYear]);
    }

    /**
     * Scope to get current year
     */
    public function scopeCurrent($query)
    {
        return $query->where('year_value', date('Y'));
    }

    /**
     * Get missing monthly reports for this year
     */
    public function getMissingMonthlyReports()
    {
        $existingMonths = $this->monthlyReports()->pluck('month_id')->toArray();
        return Month::whereNotIn('id', $existingMonths)->get();
    }

    /**
     * Get monthly reports completion percentage
     */
    public function getCompletionPercentage(): float
    {
        $totalMonths = 12;
        $completedMonths = $this->monthlyReports()->count();
        return ($completedMonths / $totalMonths) * 100;
    }
}
