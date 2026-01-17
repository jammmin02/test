<?php
    namespace Tripmate\Backend\Modules\Users\Controllers;

    use Tripmate\Backend\Core\Controller;
    use Tripmate\Backend\Modules\Users\Services\UsersService;
    use Tripmate\Backend\Common\Middleware\AuthMiddleware as amw;

    class UsersController extends Controller{
        public UsersService $service;

        // 생성자
        public function __construct($request, $response) {
            // 부모 컨트롤러 생성자
            parent::__construct($request, $response);
            
            // 서비스 생성자
            $this->service = new UsersService();

        }

        // 내 정보 조회
        public function userMyPage() {
            // 토큰 검증
            $userId = amw::tokenResponse($this->request);

            // 서비스 전달
            $result = $this->service->userMyPageService($userId);

            if ($result == "DB_EXCEPTION") {
                $this->error($result, "서버 오류입니다. 잠시 후 다시 시도해주세요.", 500);
            } else if ($result == "USER_NOT_FOUND") {
                $this->error($result, "사용자 정보를 불러올 수 없습니다. 잠시 후 다시 시도해주세요.");
            } else {
                $this->success($result);
            }
        }

        // 회원 탈퇴
        public function userSecession() {
            // 토큰 검증
            $userId = amw::tokenResponse($this->request);
            
            //비밀번호 검증여부

            // 서비스 전달
            $result = $this->service->userSecessionService($userId);

            if ($result == "DB_EXCEPTION") {
                $this->error($result, "서버 오류입니다. 잠시 후 다시 시도해주세요.", 500);
            } else {
                $this->success([$result]);
            }
            
        }
        
    }