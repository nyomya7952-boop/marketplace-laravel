<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStripeSessionIdToSoldItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sold_items', function (Blueprint $table) {
            $table->string('stripe_session_id')->nullable()->after('shipping_building_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sold_items', function (Blueprint $table) {
            $table->dropColumn('stripe_session_id');
        });
    }
}

