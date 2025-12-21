<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\BrandsTableSeeder;
use Database\Seeders\CategoriesTableSeeder;
use Database\Seeders\MasterDataTableSeeder;
use Database\Seeders\ItemsTableSeeder;
use Database\Seeders\ItemCategoryTableSeeder;
use Database\Seeders\CommentsTableSeeder;
use Database\Seeders\LikesTableSeeder;
use Database\Seeders\SoldItemsTableSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UsersTableSeeder::class,
            MasterDataTableSeeder::class,
            CategoriesTableSeeder::class,
            BrandsTableSeeder::class,
            ItemsTableSeeder::class,
            ItemCategoryTableSeeder::class,
            CommentsTableSeeder::class,
            LikesTableSeeder::class,
            SoldItemsTableSeeder::class,
        ]);
    }
}
