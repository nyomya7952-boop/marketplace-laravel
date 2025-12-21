<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData;

class MasterDataTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $conditions = [
            '良好',
            '目立った傷や汚れなし',
            'やや傷や汚れあり',
            '状態が悪い',
        ];

        foreach ($conditions as $name) {
            MasterData::firstOrCreate([
                'type' => 'condition',
                'name' => $name,
            ]);
        }

        // 支払い方法
        $paymentMethods = [
            'コンビニ支払い',
            'カード支払い',
        ];

        foreach ($paymentMethods as $name) {
            MasterData::firstOrCreate([
                'type' => 'payment_method',
                'name' => $name,
            ]);
        }
    }
}
