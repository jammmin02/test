<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users 테이블 (소셜 로그인 전용)
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('user_id');

            $table->string('email_norm', 255)
                ->nullable()
                ->comment('소셜 이메일(소문자/trim), provider별 중복 허용');

            $table->string('name', 50)
                ->unique()
                ->comment('표시용 이름(nickname)');

            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};