<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    /**
     * 사용자에 대한 JWT 발급
     *
     * @param \App\Models\User $user
     * @return string
     */
    public function issueJwtTokenForUser(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    /**
     * 현재 요청에서 사용된 JWT를 무효화 (로그아웃)
     *
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Tymon\JWTAuth\Exceptions\JWTException
     */
    public function logoutUser(): void
    {
        // 요청에서 토큰 추출
        $token = JWTAuth::getToken();

        if (!$token) {
            throw ValidationException::withMessages([
                'token' => ['요청에 토큰이 포함되어 있지 않습니다.'],
            ]);
        }

        // 2) 실제 토큰 무효화
        JWTAuth::invalidate($token);
    }
}