<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterNicknameRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Services\Auth\AuthService;

use function Symfony\Component\String\s;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * User Logout
     */
    public function logout()
    {
        $this->authService->logoutUser();
        return response()->noContent();
    }

    /**
     * 최초 가입 시 닉네임 입력 페이지
     * - JWT 발급
     * POST   /v2/auth/register/nickname
     */
    public function registerNickname(
        RegisterNicknameRequest $request
    ):JsonResponse {
        $pendingUserId = session('oauth_pending_user_id');

        if (!$pendingUserId) {
            return response()->json([
                'suscess' => false,
                'code' => 'REGISTER_NICKNAME_NOT_FOUND',
                'message' => '닉네임 등록을 위한 사용자 정보를 찾을 수 없습니다. 다시 시도해주세요.',
                'data' => null,
            ], 400); 
        }

        // 유저 조회
        $user= User::find($pendingUserId);

        if (!$user) {
            return response()->json([
                'suscess' => false,
                'code' => 'REGISTER_NICKNAME_USER_NOT_FOUND',
                'message' => '닉네임 등록을 위한 사용자 정보를 찾을 수 없습니다. 다시 시도해주세요.',
                'data' => null,
            ], 400); 
        }

        // 이미 닉네임이 설정된 경우
        if (!is_null($user->name)){
            return response()->json([
                'suscess' => false,
                'code' => 'REGISTER_NICKNAME_ALREADY_SET',
                'message' => '이미 닉네임이 설정된 사용자입니다.',
                'data' => null,
            ], 400); 
        }

        // 닉네임 설정
        $user->name = $request->input('name');
        $user->save();

        // JWT 발급
        $token = $this->authService->issueJwtTokenForUser($user);

        // 응답 반환
        return response()->json([
            'success' => true,
            'code' => 'REGISTER_NICKNAME_SUCCESS',
            'message' => '닉네임이 성공적으로 등록되었습니다.',
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ],
        ]);
    }
}