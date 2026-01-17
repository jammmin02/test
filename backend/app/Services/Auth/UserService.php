<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Auth\UserRepository;
use Illuminate\Validation\ValidationException;
use App\Repositories\Auth\SocialAccountRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserService
{
    // 프로퍼티 정의
    protected UserRepository $userRepository;
    protected SocialAccountRepository $socialAccountRepository;

    // 생성자에서 리포지토리 주입
    public function __construct(
        UserRepository $userRepository,
        SocialAccountRepository $socialAccountRepository
    ) {
        $this->userRepository = $userRepository;
        $this->socialAccountRepository = $socialAccountRepository;
    }

    /**
     * 현재 로그인한 사용자 정보 조회
     */
    public function currentUser(): User
    {
        $user = Auth::user();

        if(!$user){
            throw ValidationException::withMessages([  
                "email" => ["유저 정보를 찾을 수 없습니다."]
                ]);
        }

        return $user;
    }

    /**
     * 회원 삭제
     * - 소셜 계정 정보도 함께 삭제
     * - user 삭제
     * - 토큰 삭제
     * @param User $user
     * @return void
     */
    public function deleteUserComplete(User $user): void
    {
        DB::transaction(function () use ($user) {
            // 소셜 계정 정보 삭제
            $this->socialAccountRepository->deleteByUserId($user->user_id);

            // user 삭제
            $this->userRepository->deleteById($user->user_id);

            // 토큰 삭제
            $user->tokens()->delete();
        });
    }
}