<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('yearly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained('years')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->string('file_url', 500)->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable();
            $table->date('report_date'); // Auto-generated: December 31st
            $table->string('created_by', 100);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Unique constraint for year
            $table->unique('year_id', 'unique_yearly_report');
            
            // Indexes for performance
            $table->index('year_id', 'idx_year');
            $table->index('status', 'idx_status');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yearly_reports');
    }
};
