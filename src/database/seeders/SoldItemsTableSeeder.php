<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Item;
use App\Models\SoldItem;
use App\Models\MasterData;

class SoldItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $paymentMap = MasterData::where('type', 'payment_method')->pluck('id', 'name');

        $rows = [
            [
                'email' => 'hanako@example.com',
                'item' => 'ショルダーバッグ',
                'payment' => 'カード支払い',
                'postal_code' => '150-0001',
                'address' => '東京都渋谷区神宮前1-1-1',
                'building' => 'コーポ渋谷101',
            ],
            [
                'email' => 'jiro@example.com',
                'item' => 'HDD',
                'payment' => 'カード支払い',
                'postal_code' => '530-0001',
                'address' => '大阪府大阪市北区梅田1-1-1',
                'building' => null,
            ],
            [
                'email' => 'mina@example.com',
                'item' => 'メイクセット',
                'payment' => 'コンビニ支払い',
                'postal_code' => '460-0008',
                'address' => '愛知県名古屋市中区栄1-1-1',
                'building' => 'サカエタワー12F',
            ],
            [
                'email' => 'nyomya7952@gmail.com',
                'item' => 'コーヒーミル',
                'payment' => 'カード支払い',
                'postal_code' => '060-0001',
                'address' => '北海道札幌市中央区北一条西1-1-1',
                'building' => null,
            ],
        ];

        foreach ($rows as $row) {
            $user = User::where('email', $row['email'])->first();
            $item = Item::where('name', $row['item'])->first();
            $paymentId = $paymentMap[$row['payment']] ?? null;

            if (!$user || !$item || !$paymentId) {
                continue;
            }

            SoldItem::firstOrCreate(
                ['item_id' => $item->id, 'user_id' => $user->id],
                [
                    'payment_method_id' => $paymentId,
                    'shipping_postal_code' => $row['postal_code'],
                    'shipping_address' => $row['address'],
                    'shipping_building_name' => $row['building'],
                ]
            );

            // 売却済みフラグを立てる
            $item->update(['is_sold' => 'sold']);
        }
    }
}
