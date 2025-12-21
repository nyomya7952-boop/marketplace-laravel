<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $brands = [
            'Nike',
            'Adidas',
            'Puma',
            'New Balance',
            'Uniqlo',
            'GU',
            'ZARA',
            'The North Face',
            'Starbacks',
            '西芝',
            'Rolax',
            'なし',
        ];

        foreach ($brands as $name) {
            Brand::firstOrCreate(['name' => $name]);
        }
    }
}
