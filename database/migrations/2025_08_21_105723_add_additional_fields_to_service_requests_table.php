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
            $table->integer('family_members')->nullable()->after('family_book');
            $table->integer('female_members')->nullable()->after('family_members');
            $table->foreignId('village_id')->nullable()->constrained()->onDelete('set null')->after('female_members');
            $table->foreignId('commune_id')->nullable()->constrained()->onDelete('set null')->after('village_id');
            $table->foreignId('district_id')->nullable()->constrained()->onDelete('set null')->after('commune_id');
            $table->foreignId('province_id')->nullable()->constrained()->onDelete('set null')->after('district_id');
            $table->foreignId('occupation_id')->nullable()->constrained()->onDelete('set null')->after('province_id');
            $table->foreignId('usage_type_id')->nullable()->constrained()->onDelete('set null')->after('occupation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['village_id']);
            $table->dropForeign(['commune_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['province_id']);
            $table->dropForeign(['occupation_id']);
            $table->dropForeign(['usage_type_id']);
            $table->dropColumn([
                'family_members',
                'female_members',
                'village_id',
                'commune_id', 
                'district_id',
                'province_id',
                'occupation_id',
                'usage_type_id'
            ]);
        });
    }
};
