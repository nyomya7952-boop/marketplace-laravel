<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyIsSoldColumnInItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // boolean型からstring型に変更
        // null = 未購入、'pending' = 入金待ち、'sold' = 購入済み

        // 既存のデータを変換（true -> 'sold', false -> null）
        DB::statement('UPDATE items SET is_sold = CASE WHEN is_sold = 1 THEN "sold" WHEN is_sold = 0 THEN NULL END');

        // カラムの型を変更（MySQL/MariaDBの場合）
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE items MODIFY COLUMN is_sold VARCHAR(10) NULL DEFAULT NULL');
        } else {
            // PostgreSQLの場合
            DB::statement('ALTER TABLE items ALTER COLUMN is_sold TYPE VARCHAR(10) USING CASE WHEN is_sold = true THEN \'sold\'::VARCHAR(10) WHEN is_sold = false THEN NULL ELSE NULL END');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // string型からboolean型に戻す

        // 既存のデータを変換
        DB::statement('UPDATE items SET is_sold = CASE WHEN is_sold = "sold" THEN 1 ELSE 0 END');

        // カラムの型を変更（MySQL/MariaDBの場合）
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE items MODIFY COLUMN is_sold BOOLEAN DEFAULT FALSE');
        } else {
            // PostgreSQLの場合
            DB::statement('ALTER TABLE items ALTER COLUMN is_sold TYPE BOOLEAN USING CASE WHEN is_sold = \'sold\' THEN true ELSE false END');
        }
    }
}

