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
        Schema::create('months', function (Blueprint $table) {
            $table->id();
            $table->string('month', 20);
            $table->timestamps();
        });

        // Insert months data
        $months = [
            ['id' => 1, 'month' => 'January'],
            ['id' => 2, 'month' => 'February'],
            ['id' => 3, 'month' => 'March'],
            ['id' => 4, 'month' => 'April'],
            ['id' => 5, 'month' => 'May'],
            ['id' => 6, 'month' => 'June'],
            ['id' => 7, 'month' => 'July'],
            ['id' => 8, 'month' => 'August'],
            ['id' => 9, 'month' => 'September'],
            ['id' => 10, 'month' => 'October'],
            ['id' => 11, 'month' => 'November'],
            ['id' => 12, 'month' => 'December'],
        ];

        foreach ($months as $month) {
            DB::table('months')->insert([
                'id' => $month['id'],
                'month' => $month['month'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('months');
    }
};
