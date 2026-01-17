<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\UserService;
use App\Http\Requests\Auth\AuthVerificationRequest;
use App\Http\Resources\UserResource;

class UsersController extends Controller
{
    private UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * User Mypage
     * GET /v2/auth/user
     */
    public function getCurrentUser() 
    {
        // 현재 사용자 정보 조회
        $user = $this->userService->currentUser();

        // 응답 반환
        return response()->json([
            'success' => true,
            'code' => 'SUCCESS',
            'message' => '현재 사용자 정보를 반환합니다.',
            'data' =>[
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * User delete - 회원삭제
     * DELETE /v2/user/me
     */
    public function deleteCurrentUser(AuthVerificationRequest $request) 
    {  
        // 현재 사용자 정보 조회
        $user = $request->user();

        if (!$user){
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => '사용자를 찾을 수 없습니다.',
                'data' => null,
            ], 404);
        }

        // 회원 삭제 처리
        $this->userService->deleteUserComplete($user->user_id);

        // 응답 반환 (204 No Content)
        return response()->noContent();
    }

}