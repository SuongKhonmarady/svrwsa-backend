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
        // Add foreign key columns to districts table
        Schema::table('districts', function (Blueprint $table) {
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
        });

        // Add foreign key columns to communes table
        Schema::table('communes', function (Blueprint $table) {
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
        });

        // Add foreign key columns to villages table
        Schema::table('villages', function (Blueprint $table) {
            $table->foreignId('commune_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key columns from villages table
        Schema::table('villages', function (Blueprint $table) {
            $table->dropForeign(['commune_id']);
            $table->dropColumn('commune_id');
        });

        // Remove foreign key columns from communes table
        Schema::table('communes', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropColumn('district_id');
        });

        // Remove foreign key columns from districts table
        Schema::table('districts', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropColumn('province_id');
        });
    }
};
