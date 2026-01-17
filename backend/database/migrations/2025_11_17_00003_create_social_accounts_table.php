<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * social_accounts 테이블
     */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->bigIncrements('social_account_id');

            $table->unsignedBigInteger('user_id');
            $table->string('provider', 50);
            $table->string('provider_user_id', 255);

            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            // UNIQUE
            $table->unique(
                ['provider', 'provider_user_id'],
                'uq_social_account_provider_user'
            );

            $table->unique(
                'user_id',
                'uq_social_account_user'
            );

            // FK
            $table->foreign('user_id', 'fk_social_account_user')
                ->references('user_id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade'); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};