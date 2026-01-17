<?php

namespace App\Services\Auth;

use App\Models\User;

/**
 * 소셜 인증 결과를 담는 DTO
 */
class SocialAuthResult
{
  public function __construct(
    public ?User $user,
    public ?string $jwtToken,

    // 같은 이메일이 있을 경우 계정 연결 여부 확인
    public bool $needLinkSocialAccount,
    // 신규 유저는 닉네임 입력 필요
    public bool $userNeedsname,
    // 회원가입 필요
    public bool $registerRequired
  ) {}
}