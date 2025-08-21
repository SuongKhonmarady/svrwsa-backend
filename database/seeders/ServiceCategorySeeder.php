<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\District;
use App\Models\Commune;
use App\Models\Occupation;
use App\Models\UsageType;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Provinces
        $provinces = [
            ['name' => 'ស្វាយរៀង'],
        ];

        foreach ($provinces as $province) {
            Province::create($province);
        }

        // Create Districts
        $districts = [
            ['name' => 'ស្វាយរៀង', 'province_id' => 1],
            ['name' => 'ស្វាយជ្រំ', 'province_id' => 1],
        ];

        foreach ($districts as $district) {
            District::create($district);
        }

        // Create Communes
        $communes = [
            ['name' => 'ស្វាយរៀង', 'district_id' => 1],
            ['name' => 'ពោធិតាហោ', 'district_id' => 1],
            ['name' => 'គយត្របែក', 'district_id' => 1],
            ['name' => 'ចេក', 'district_id' => 1],
            ['name' => 'ព្រៃឆ្លាក់', 'district_id' => 1],
            ['name' => 'សង្ឃ័រ', 'district_id' => 1],
            ['name' => 'ស្វាយតឿ', 'district_id' => 1],
            ['name' => 'ស្វាយជ្រំ', 'district_id' => 2],
            ['name' => 'តាសួស', 'district_id' => 2],
            ['name' => 'បាសាក់', 'district_id' => 2],
            ['name' => 'កំពង់ចម្លង', 'district_id' => 2],
            ['name' => 'ពោធិរាជ', 'district_id' => 2],
        ];

        foreach ($communes as $commune) {
            Commune::create($commune);
        }



        // Create Occupations
        $occupations = [
            ['name' => 'មន្ត្រីរាជការ'],
            ['name' => 'អាជីវករ'],
            ['name' => 'បុគ្គលិក'],
            ['name' => 'វត្តអារាម'],
            ['name' => 'មន្ទីរពេទ្យ'],
            ['name' => 'ផ្ទះសំណាក់'],
            ['name' => 'សណ្ឋាគារ'],
            ['name' => 'សាលារៀន'],
            ['name' => 'លាងឡាន/ម៉ូតូ'],
        ];

        foreach ($occupations as $occupation) {
            Occupation::create($occupation);
        }

        // Create Usage Types
        $usageTypes = [
            ['name' => 'ជីវភាព'],
            ['name' => 'អាជីវកម្ម'],
            ['name' => 'ស្ថាប័នរដ្ឋ'],
        ];

        foreach ($usageTypes as $usageType) {
            UsageType::create($usageType);
        }
    }
}
