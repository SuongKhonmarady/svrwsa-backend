<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Year;
use App\Models\Month;
use App\Models\MonthlyReport;
use App\Models\YearlyReport;
use Carbon\Carbon;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get current year and some previous years
        $currentYear = date('Y');
        $years = Year::whereBetween('year_value', [2022, $currentYear])->get();
        $months = Month::all();
        
        // Create sample monthly reports
        foreach ($years as $year) {
            $numberOfMonths = $year->year_value == $currentYear ? 
                min(date('n'), 12) : // Current year: only up to current month
                rand(8, 12); // Previous years: random completion
            
            $selectedMonths = $months->random($numberOfMonths);
            
            foreach ($selectedMonths as $month) {
                $status = rand(1, 10) > 2 ? 'published' : 'draft'; // 80% published, 20% draft
                
                $report = MonthlyReport::create([
                    'year_id' => $year->id,
                    'month_id' => $month->id,
                    'title' => "Monthly Water Quality Report - {$month->month} {$year->year_value}",
                    'description' => "Comprehensive water quality analysis and testing results for {$month->month} {$year->year_value}. This report includes water quality parameters, treatment processes, and compliance with regulatory standards.",
                    'status' => $status,
                    'created_by' => 'System Admin',
                    'published_at' => $status === 'published' ? 
                        Carbon::create($year->year_value, $month->id, rand(25, 28))->setTime(rand(9, 17), rand(0, 59)) : 
                        null
                ]);
                
                // Randomly assign report dates within the month
                $reportDate = Carbon::create($year->year_value, $month->id, 1)->endOfMonth();
                $report->update(['report_date' => $reportDate]);
            }
        }
        
        // Create sample yearly reports
        foreach ($years as $year) {
            // Only create yearly reports for previous years and randomly for current year
            if ($year->year_value < $currentYear || rand(1, 3) == 1) {
                $status = rand(1, 10) > 3 ? 'published' : 'draft'; // 70% published, 30% draft
                
                $report = YearlyReport::create([
                    'year_id' => $year->id,
                    'title' => "Annual Water Service Report {$year->year_value}",
                    'description' => "Comprehensive annual report covering all water service activities, infrastructure improvements, regulatory compliance, and future planning for {$year->year_value}. This report provides a complete overview of SVRWSA's performance and achievements throughout the year.",
                    'status' => $status,
                    'created_by' => 'System Admin',
                    'published_at' => $status === 'published' ? 
                        Carbon::create($year->year_value, 12, rand(28, 31))->setTime(rand(9, 17), rand(0, 59)) : 
                        null
                ]);
                
                // Set report date to December 31st
                $reportDate = Carbon::create($year->year_value, 12, 31);
                $report->update(['report_date' => $reportDate]);
            }
        }
        
        $this->command->info('Sample reports have been created successfully!');
        $this->command->info('Monthly reports: ' . MonthlyReport::count());
        $this->command->info('Yearly reports: ' . YearlyReport::count());
    }
}
