<?php
    namespace Tripmate\Backend\Modules\Users\Services;
    
    use Tripmate\Backend\Modules\Users\Repositories\UsersReadRepository as Repository;

    class UsersService {
        public Repository $repository;

        // 생성자 
        public function __construct() {
            // db 객체
            $this->repository = new Repository();
        }

        // 내 정보 조회
        public function userMyPageService($userId) {
            //db 호출
            $result = $this->repository->userMyPageRepository($userId);
            
            return $result;
        }

        // 회원 탈퇴
        public function userSecessionService($userId) {
            // DB에 전달
            $result = $this->repository->userSecessionRepository($userId);
        
            return $result;
        }
    }
