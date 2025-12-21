<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Category;

class ItemCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // アイテム名と紐づけるカテゴリ名の対応表
        $itemCategoryMap = [
            '腕時計' => ['ファッション', 'メンズ', 'アクセサリー'],
            'HDD' => ['家電'],
            '玉ねぎ3束' => ['キッチン'],
            '革靴' => ['ファッション', 'メンズ'],
            'ノートPC' => ['家電'],
            'マイク' => ['家電'],
            'ショルダーバッグ' => ['ファッション', 'レディース'],
            'タンブラー' => ['キッチン'],
            'コーヒーミル' => ['キッチン'],
            'メイクセット' => ['コスメ', 'レディース'],
        ];

        foreach ($itemCategoryMap as $itemName => $categoryNames) {
            $item = Item::where('name', $itemName)->first();
            if (!$item) {
                continue;
            }

            $categoryIds = Category::whereIn('name', $categoryNames)->pluck('id')->toArray();
            if (count($categoryIds) === 0) {
                continue;
            }

            // 重複を避けつつ紐付け
            $item->categories()->syncWithoutDetaching($categoryIds);
        }
    }
}
