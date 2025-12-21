<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Item;
use App\Models\Comment;

class CommentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $comments = [
            ['email' => 'taro@example.com', 'item' => '腕時計', 'content' => 'デザインが気に入りました。'],
            ['email' => 'hanako@example.com', 'item' => 'ショルダーバッグ', 'content' => 'サイズ感はどのくらいですか？'],
            ['email' => 'jiro@example.com', 'item' => 'HDD', 'content' => '発送はいつ頃になりますか？'],
            ['email' => 'mina@example.com', 'item' => 'メイクセット', 'content' => '未使用でしょうか？'],
            ['email' => 'nyomya7952@gmail.com', 'item' => 'コーヒーミル', 'content' => '動作に問題はありませんか？'],
        ];

        foreach ($comments as $row) {
            $user = User::where('email', $row['email'])->first();
            $item = Item::where('name', $row['item'])->first();

            if (!$user || !$item) {
                continue;
            }

            Comment::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'content' => $row['content'],
            ]);
        }
    }
}
