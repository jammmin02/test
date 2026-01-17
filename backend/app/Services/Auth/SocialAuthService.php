<?php
namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Auth\UserRepository;
use App\Repositories\Auth\SocialAccountRepository;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAuthService
{
    // repository 프로퍼티
    protected UserRepository $userRepository;
    protected SocialAccountRepository $socialAccountRepository;

    // 생성자 주입
    public function __construct(
        UserRepository $userRepository,
        SocialAccountRepository $socialAccountRepository,
    ) {
        $this->userRepository = $userRepository;
        $this->socialAccountRepository = $socialAccountRepository;
    }

    /**
     * 회원가입 처리
     * @param string $provider
     * @param ProviderUser $providerUser
     * @return SocialAuthResult
     */
    public function handleRegister(
        string $provider,
        ProviderUser $providerUser
    ) : SocialAuthResult {

        $rawEmail = $providerUser->getEmail();
        $emailNorm = $this->normalizeEmail($rawEmail);
        $providerUserId = $providerUser->getId();

        // 트랜잭션 처리
        return DB::transaction(function () use (
            $provider,
            $emailNorm,
            $providerUserId, 
            ) {
                // 이미 가입 되어 있는 경우
                $social = $this->socialAccountRepository
                    ->findByProviderAndProviderUserId(
                        $provider,
                        $providerUserId
                    );
                
                if ($social) {
                    $user = $social->user;

                    return new SocialAuthResult(
                        user: $user,
                        jwtToken: $user->createToken('auth_token')->plainTextToken,
                        needLinkSocialAccount: false,
                        userNeedsname: false,
                        registerRequired: false
                    );
            }

            // 신규 가입 처리
            $user = $this->userRepository->findByEmailNorm($emailNorm);

            // 기존 이메일이 있는 경우
            if ($user) {
                // 소셜 계정 연결 필요
                return new SocialAuthResult(
                    user: $user,
                    jwtToken: null,
                    needLinkSocialAccount: true,
                    userNeedsname: false,
                    registerRequired: false
                );
            }

            // 신규 유저 생성
            $user = $this->userRepository->createWithEmail($emailNorm);

            $this->socialAccountRepository->createForUser(
                $user->user_id,
                $provider,
                $providerUserId
            );

            // 닉네임 입력 전까지 JWT 발급하지 않음
            return new SocialAuthResult(
                user: $user,
                jwtToken: null,
                needLinkSocialAccount: false,
                userNeedsname: true,
                registerRequired: false
            );
        });
    }

    /**
     * 소셜 로그인 처리
     * @param string $provider
     * @param ProviderUser $providerUser
     * @return SocialAuthResult
     */
    public function handleLogin(
        string $provider,
        ProviderUser $providerUser
    ) : SocialAuthResult {

        $rawEmail = $providerUser->getEmail();
        $emailNorm = $this->normalizeEmail($rawEmail);
        $providerUserId = $providerUser->getId();

        // social account 조회
        $social = $this->socialAccountRepository
            ->findByProviderAndProviderUserId(
                $provider,
                $providerUserId
            );

        // 소셜 계정이 연결된 경우
        if ($social) {
            $user = $social->user;

            return new SocialAuthResult(
                user: $user,
                jwtToken: $user->createToken('auth_token')->plainTextToken,
                needLinkSocialAccount: false,
                userNeedsname: false,
                registerRequired: false
            );
        }

        /**
         * social account는 없지만 동일 이메일이 있는 경우
         * - 계정 연결 여부 확인
         */ 
        $user = $this->userRepository->findByEmailNorm($emailNorm);

        if ($user) {
            return new SocialAuthResult(
                user: $user,
                jwtToken: null,
                needLinkSocialAccount: true,
                userNeedsname: false,
                registerRequired: false
            );
        }

        // 회원가입 필요한 경우
        return new SocialAuthResult(
            user: null,
            jwtToken: null,
            needLinkSocialAccount: false,
            userNeedsname: false,
            registerRequired: true
        );
    }

    /**
     * 이메일 정규화
     * 공백 제거 및 소문자 변환
     * @param string|null $email
     * @return string|null
     */
    protected function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        return mb_strtolower(trim($email));
    }

    /**
     * 소셜 계정 삭제
     * @param User $user
     * @return void
     */
    public function deleteByUserId(User $user): void {
        
        DB::transaction(function () use ($user) {
            // social account 삭제
            $this->socialAccountRepository->deleteByUserId($user->user_id);

            // 소프트 삭제
            $this->userRepository->deleteById($user->user_id);

            // 현재/전체 토큰 삭제
            $user->tokens()->delete();
        
        });
    }

    
}