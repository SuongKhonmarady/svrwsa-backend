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
        Schema::table('service_requests', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['village_id']);
            
            // Drop the village_id column
            $table->dropColumn('village_id');
            
            // Add the village text column
            $table->string('village')->nullable()->after('female_members');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Drop the village text column
            $table->dropColumn('village');
            
            // Add back the village_id column
            $table->foreignId('village_id')->nullable()->constrained()->onDelete('set null')->after('female_members');
        });
    }
};
