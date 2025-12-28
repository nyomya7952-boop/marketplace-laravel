<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Item;
use App\Models\Brand;
use App\Models\User;
use App\Models\MasterData;

class ItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 既存ユーザーがいない場合のために、シード用ユーザーを1件用意
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'seed_user',
                'email' => 'seed@example.com',
                'password' => Hash::make('password'),
                'postal_code' => '000-0000',
                'address' => 'シード用住所',
            ]);
        }

        // コンディションのマスタ（name => id）を取得
        $conditionMap = MasterData::where('type', 'condition')->pluck('id', 'name');

        $items = [
            [
                'name' => '腕時計',
                'price' => 15000,
                'brand' => 'Rolax',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'image_path' => 'items/Armani+Mens+Clock.jpg',
                'condition' => '良好',
            ],
            [
                'name' => 'HDD',
                'price' => 5000,
                'brand' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'image_path' => 'items/HDD+Hard+Disk.jpg',
                'condition' => '目立った傷や汚れなし',
            ],
            [
                'name' => '玉ねぎ3束',
                'price' => 300,
                'brand' => 'なし',
                'description' => '新鮮な玉ねぎ3束のセット',
                'image_path' => 'items/iLoveIMG+d.jpg',
                'condition' => 'やや傷や汚れあり',
            ],
            [
                'name' => '革靴',
                'price' => 4000,
                'brand' => 'なし',
                'description' => 'クラシックなデザインの革靴',
                'image_path' => 'items/Leather+Shoes+Product+Photo.jpg',
                'condition' => '状態が悪い',
            ],
            [
                'name' => 'ノートPC',
                'price' => 45000,
                'brand' => 'なし',
                'description' => '高性能なノートパソコン',
                'image_path' => 'items/Living+Room+Laptop.jpg',
                'condition' => '良好',
            ],
            [
                'name' => 'マイク',
                'price' => 8000,
                'brand' => 'なし',
                'description' => '高音質のレコーディング用マイク',
                'image_path' => 'items/Music+Mic+4632231.jpg',
                'condition' => '目立った傷や汚れなし',
            ],
            [
                'name' => 'ショルダーバッグ',
                'price' => 3500,
                'brand' => 'なし',
                'description' => 'おしゃれなショルダーバッグ',
                'image_path' => 'items/Purse+fashion+pocket.jpg',
                'condition' => 'やや傷や汚れあり',
            ],
            [
                'name' => 'タンブラー',
                'price' => 500,
                'brand' => 'なし',
                'description' => '使いやすいタンブラー',
                'image_path' => 'items/Tumbler+souvenir.jpg',
                'condition' => '状態が悪い',
            ],
            [
                'name' => 'コーヒーミル',
                'price' => 4000,
                'brand' => 'Starbacks',
                'description' => '手動のコーヒーミル',
                'image_path' => 'items/Waitress+with+Coffee+Grinder.jpg',
                'condition' => '良好',
            ],
            [
                'name' => 'メイクセット',
                'price' => 2500,
                'brand' => 'なし',
                'description' => '便利なメイクアップセット',
                'image_path' => 'items/外出メイクアップセット.jpg',
                'condition' => '目立った傷や汚れなし',
            ],
        ];

        foreach ($items as $item) {
            $conditionId = $conditionMap[$item['condition']] ?? null;

            // ブランド「なし」はnull扱い
            $brandId = null;
            if ($item['brand'] !== 'なし') {
                $brand = Brand::firstOrCreate(['name' => $item['brand']]);
                $brandId = $brand->id;
            }

            // コンディションマスタが存在しない場合はスキップ
            if (!$conditionId) {
                continue;
            }

            Item::create([
                'name' => $item['name'],
                'image_path' => $item['image_path'],
                'is_sold' => null,
                'user_id' => $user->id,
                'brand_id' => $brandId,
                'price' => $item['price'],
                'description' => $item['description'],
                'condition_id' => $conditionId,
            ]);
        }
    }
}
