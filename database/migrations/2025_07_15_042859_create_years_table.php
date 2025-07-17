<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('years', function (Blueprint $table) {
            $table->id();
            $table->integer('year_value')->unique();
            $table->timestamps();
        });

        // Insert initial years data
        $years = [];
        for ($year = 2014; $year <= 2030; $year++) {
            $years[] = [
                'year_value' => $year,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        DB::table('years')->insert($years);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('years');
    }
};
