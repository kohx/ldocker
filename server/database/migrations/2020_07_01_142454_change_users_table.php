<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // provider_id を id の後ろに追加して add for socialite のコメントを入れる
            $table->string('provider_id')->nullable()->after('id')->comment('add for socialite');
            // provider_name を provider_id の後ろに追加して add for socialite のコメントを入れる
            $table->string('provider_name')->nullable()->after('provider_id')->comment('add for socialite');
            // nickname を name の後ろに追加して add for socialite のコメントを入れる
            $table->string('nickname')->nullable()->after('name')->comment('add for socialite');
            // avatar を email の後ろに追加して add for socialite のコメントを入れる
            $table->string('avatar')->nullable()->after('email')->comment('add for socialite');
            // socialiteでログインした場合メールアドレスがない場合があるのでnull許容に変更
            $table->string('email')->nullable()->change()->comment('change to nullable for socialite');
            // socialiteでログインした場合パスワードがないのでnull許容に変更
            $table->string('password')->nullable()->change()->comment('change to nullable for socialite');
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
            $table->dropColumn(['provider_id', 'provider_name', 'nickname', 'avatar']);
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
}
