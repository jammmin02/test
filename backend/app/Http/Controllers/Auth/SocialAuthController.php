<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAuthService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // 프로퍼티 정의
    protected SocialAuthService $socialAuthService;

    // 생성자에서 서비스 주입
    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }

    /**
     * 내부에서 사용할 state 세션 키 생성
     * @param string $provider
     * @param string $mode 'register' | 'login'
     */
    protected function getStateSessionKey(string $provider, string $mode): string
    {
        return "oauth_state_{$provider}_{$mode}";
    }

    /**
     * 회원가입 
     * GET /v2/auth/{provider}/register
     * @param string $provider
     */
    public function redirectToRegister(string $provider)
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        // 기본값: Google 등 대부분의 provider는 기존 방식 유지
        if ($provider === 'line') {
            // LINE은 state 필수 → 직접 생성해서 붙이기
            $state      = Str::random(40);
            $sessionKey = $this->getStateSessionKey($provider, 'register');

            // 세션에 저장 (콜백에서 검증용)
            session([$sessionKey => $state]);

            $redirectUrl = $driver
                ->with(['state' => $state])
                ->redirect()
                ->getTargetUrl();
        } else {
            // Google 등: 기존 stateless 방식 유지
            $redirectUrl = $driver
                ->stateless()
                ->redirect()
                ->getTargetUrl();
        }

        return response()->json([
            'success' => true,
            'code'    => 'SUCCESS',
            'message' => '소셜 회원가입 페이지로 리디렉션합니다.',
            'data'    => [
                'mode'         => 'register',
                'redirect_url' => $redirectUrl,
            ],
        ]);
    }

    /**
     * 회원가입 콜백 처리
     * GET /v2/auth/{provider}/register/callback
     * @param string $provider
     */
    public function handleRegisterCallback(
        Request $request,
        string $provider
    ) {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        if ($provider === 'line') {
            // LINE 전용: state 검증
            $sessionKey     = $this->getStateSessionKey($provider, 'register');
            $expectedState  = session($sessionKey);
            $receivedState  = $request->query('state');

            if (!$expectedState || !$receivedState || !hash_equals($expectedState, $receivedState)) {
                // state 불일치 → 보안상 에러 처리
                return response()->json([
                    'success' => false,
                    'code'    => 'OAUTH_STATE_MISMATCH',
                    'message' => '소셜 인증 state 값이 유효하지 않습니다.',
                    'data'    => null,
                ], 400);
            }

            // 한 번 사용한 state는 제거 (재사용 방지)
            session()->forget($sessionKey);

            // state는 우리가 검증했으므로 stateless로 user 조회
            $providerUser = $driver->stateless()->user();
        } else {
            // Google 등: 기존 방식 유지
            $providerUser = $driver->stateless()->user();
        }

        // 이메일 필수
        if (!$providerUser->getEmail()) {
            // error 엔드포인트로 리디렉션
            return redirect()->route('auth.error.email-required');
        }

        $result = $this->socialAuthService->handleRegister(
            $provider,
            $providerUser
        );

        $userResource = $result->user
            ? new UserResource($result->user)
            : null;

        /**
         * 여기서 "닉네임이 아직 없는 상태"를 처리
         * - 세션에 pending user id 저장
         */
        if ($result->userNeedsname) {
            if ($result->user) {
                session(['oauth_pending_user_id' => $result->user->user_id]);
            }

            return response()->json([
                'success' => true,
                'code'    => 'NICKNAME_REQUIRED',
                'message' => '소셜 회원가입이 처리되었습니다. 닉네임 입력이 필요합니다.',
                'data'    => [
                    'user'                      => $userResource,
                    'access_token'              => null,
                    'need_link_social_account'  => $result->needLinkSocialAccount,
                    'user_needs_name'           => true,
                    'register_required'         => $result->registerRequired,
                ],
            ]);
        }

        // 닉네임까지 이미 있는 경우 → 바로 JWT 포함해서 응답
        return response()->json([
            'success' => true,
            'code'    => 'SUCCESS',
            'message' => '소셜 회원가입이 처리되었습니다.',
            'data'    => [
                'user'                      => $userResource,
                'access_token'              => $result->jwtToken,
                'need_link_social_account'  => $result->needLinkSocialAccount,
                'user_needs_name'           => $result->userNeedsname,
                'register_required'         => $result->registerRequired,
            ],
        ]);
    }

    /**
     * 로그인
     * GET /v2/auth/{provider}/login
     */
    public function redirectToLogin(string $provider)
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        if ($provider === 'line') {
            // LINE: state 생성 + 세션 저장 + URL에 붙이기
            $state      = Str::random(40);
            $sessionKey = $this->getStateSessionKey($provider, 'login');

            session([$sessionKey => $state]);

            $redirectUrl = $driver
                ->with(['state' => $state])
                ->redirect()
                ->getTargetUrl();
        } else {
            // Google 등: 기존 stateless 방식 유지
            $redirectUrl = $driver
                ->stateless()
                ->redirect()
                ->getTargetUrl();
        }

        return response()->json([
            'success' => true,
            'code'    => 'SUCCESS',
            'message' => '소셜 로그인 페이지로 리디렉션합니다.',
            'data'    => [
                'mode'         => 'login',
                'redirect_url' => $redirectUrl,
            ],
        ]);
    }

    /**
     * 로그인 콜백 처리
     * GET /v2/auth/{provider}/login/callback
     */
    public function handleLoginCallback(
        Request $request,
        string $provider
    ) {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        if ($provider === 'line') {
            // LINE 전용: state 검증
            $sessionKey     = $this->getStateSessionKey($provider, 'login');
            $expectedState  = session($sessionKey);
            $receivedState  = $request->query('state');

            if (!$expectedState || !$receivedState || !hash_equals($expectedState, $receivedState)) {
                return response()->json([
                    'success' => false,
                    'code'    => 'OAUTH_STATE_MISMATCH',
                    'message' => '소셜 인증 state 값이 유효하지 않습니다.',
                    'data'    => null,
                ], 400);
            }

            session()->forget($sessionKey);

            $providerUser = $driver->stateless()->user();
        } else {
            // Google 등: 기존 방식 유지
            $providerUser = $driver->stateless()->user();
        }

        // 이메일 필수
        if (!$providerUser->getEmail()) {
            // error 엔드포인트로 리디렉션
            return redirect()->route('auth.error.email-required');
        }

        $result = $this->socialAuthService->handleLogin(
            $provider,
            $providerUser
        );

        $userResource = $result->user
            ? new UserResource($result->user)
            : null;
        
        // 가입되지 않은 계정인 경우 register-required 응답
        if ($result->registerRequired) {
            return response()->json([
                'success' => false,
                'code'    => 'REGISTER_REQUIRED',
                'message' => '미가입 계정으로 로그인 시도 했습니다. 회원가입이 필요합니다.',
                'data'    => [
                    'register_required' => true,
                ],
            ], 400);
        }

        /**
         * 로그인인데 닉네임이 아직 없는 유저인 경우
         * - 회원은 존재하지만 name=null 인 상태
         * - 세션에 pending user id 저장
         * - access_token 은 발급하지 않고 닉네임 입력 유도
         */
        if ($result->userNeedsname) {
            if ($result->user) {
                session(['oauth_pending_user_id' => $result->user->user_id]);
            }

            return response()->json([
                'success' => true,
                'code'    => 'NICKNAME_REQUIRED',
                'message' => '소셜 로그인이 처리되었습니다. 닉네임 입력이 필요합니다.',
                'data'    => [
                    'user'                      => $userResource,
                    'access_token'              => null,
                    'need_link_social_account'  => $result->needLinkSocialAccount,
                    'user_needs_name'           => true,
                    'register_required'         => $result->registerRequired,
                ],
            ]);
        }

        // 정상 로그인 처리 (닉네임도 이미 있는 상태)
        return redirect('/oauth-complete.html');

        // JSON으로 바로 JWT 내려줄 경우에는 아래 주석을 사용
        /*
        return response()->json([
            'success' => true,
            'code'    => 'SUCCESS',
            'message' => '소셜 로그인이 처리되었습니다.',
            'data'    => [
                'user'                      => $userResource,
                'access_token'              => $result->jwtToken,
                'need_link_social_account'  => $result->needLinkSocialAccount,
                'user_needs_name'           => $result->userNeedsname,
                'register_required'         => $result->registerRequired,
            ],
        ]);
        */
    }

    /**
     * 이메일 필수 에러
     * GET /v2/auth/error/email-required
     */
    public function emailRequiredError()
    {
        return response()->json([
            'success' => false,
            'code'    => 'EMAIL_REQUIRED',
            'message' => '이메일 정보가 제공되지 않아 소셜 인증을 진행할 수 없습니다. 이메일 제공이 필수입니다.',
            'data'    => null,
        ], 400);
    }

    /**
     * 기존 계정과 소셜 계정 연결 여부 확인
     * GET /v2/auth/account/link/confirm
     */
    public function showLinkConfirm()
    {
        return response()->json([
            'success' => true,
            'code'    => 'SUCCESS',
            'message' => '기존 계정과 소셜 계정 연결 여부를 확인합니다.',
            'data'    => [
                'message' => '기존 계정과 소셜 계정을 연결하시겠습니까?',
            ],
        ]);
    }

    /**
     * 미가입 계정으로 로그인 시도 했을 경우
     * GET /v2/auth/register-required
     */
    public function registerRequired()
    {
        return response()->json([
            'success' => false,
            'code'    => 'REGISTER_REQUIRED',
            'message' => '미가입 계정으로 로그인 시도 했습니다. 회원가입이 필요합니다.',
            'data'    => null,
        ], 400);
    }
}