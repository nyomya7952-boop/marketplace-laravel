<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Item;
use App\Models\Like;

class LikesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pairs = [
            ['email' => 'taro@example.com', 'item' => '腕時計'],
            ['email' => 'hanako@example.com', 'item' => 'HDD'],
            ['email' => 'jiro@example.com', 'item' => 'ノートPC'],
            ['email' => 'mina@example.com', 'item' => 'ショルダーバッグ'],
            ['email' => 'nyomya7952@gmail.com', 'item' => 'コーヒーミル'],
        ];

        foreach ($pairs as $pair) {
            $user = User::where('email', $pair['email'])->first();
            $item = Item::where('name', $pair['item'])->first();

            if (!$user || !$item) {
                continue;
            }

            Like::firstOrCreate([
                'user_id' => $user->id,
                'item_id' => $item->id,
            ]);
        }
    }
}
