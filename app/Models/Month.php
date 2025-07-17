<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Month extends Model
{
    protected $fillable = [
        'month'
    ];

    /**
     * Get all monthly reports for this month
     */
    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyReport::class);
    }

    /**
     * Get the month name
     */
    public function getNameAttribute(): string
    {
        return $this->month;
    }

    /**
     * Get the month number (1-12)
     */
    public function getNumberAttribute(): int
    {
        return $this->id;
    }

    /**
     * Get the short month name (Jan, Feb, etc.)
     */
    public function getShortNameAttribute(): string
    {
        return substr($this->month, 0, 3);
    }

    /**
     * Get the number of days in this month for a given year
     */
    public function getDaysInMonth($year): int
    {
        return cal_days_in_month(CAL_GREGORIAN, $this->id, $year);
    }

    /**
     * Get the last day of this month for a given year
     */
    public function getLastDayOfMonth($year): string
    {
        $daysInMonth = $this->getDaysInMonth($year);
        return sprintf('%d-%02d-%02d', $year, $this->id, $daysInMonth);
    }

    /**
     * Scope to get months by quarter
     */
    public function scopeByQuarter($query, $quarter)
    {
        $quarters = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12]
        ];

        if (isset($quarters[$quarter])) {
            return $query->whereIn('id', $quarters[$quarter]);
        }

        return $query;
    }
}
