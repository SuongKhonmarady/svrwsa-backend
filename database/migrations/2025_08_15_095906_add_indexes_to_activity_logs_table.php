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
        Schema::table('activity_logs', function (Blueprint $table) {
            // Add indexes for commonly queried columns
            $table->index('user_id', 'idx_activity_logs_user_id');
            $table->index('action', 'idx_activity_logs_action');
            $table->index('table_name', 'idx_activity_logs_table_name');
            $table->index('record_id', 'idx_activity_logs_record_id');
            $table->index('created_at', 'idx_activity_logs_created_at');
            
            // Composite index for common query patterns
            $table->index(['user_id', 'created_at'], 'idx_activity_logs_user_created');
            $table->index(['table_name', 'record_id'], 'idx_activity_logs_table_record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Drop indexes in reverse order
            $table->dropIndex('idx_activity_logs_table_record');
            $table->dropIndex('idx_activity_logs_user_created');
            $table->dropIndex('idx_activity_logs_created_at');
            $table->dropIndex('idx_activity_logs_record_id');
            $table->dropIndex('idx_activity_logs_table_name');
            $table->dropIndex('idx_activity_logs_action');
            $table->dropIndex('idx_activity_logs_user_id');
        });
    }
};
