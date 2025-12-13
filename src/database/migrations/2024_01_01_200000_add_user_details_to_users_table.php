<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserDetailsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
            $table->string('postal_code')->nullable()->after('is_active');
            $table->string('address')->nullable()->after('postal_code');
            $table->string('building_name')->nullable()->after('address');
            $table->string('profile_image_path')->nullable()->after('building_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'postal_code', 'address', 'building_name', 'profile_image_path']);
        });
    }
}

