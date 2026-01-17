<?php
    namespace Tripmate\Backend\Modules\Auth\Services;

    use Tripmate\Backend\Common\Utils\Password;
    use Tripmate\Backend\Modules\Auth\Repositories\UserRepository;
    use Tripmate\Backend\Common\Middleware\AuthMiddleware as amw;

    // 서비스 로직
    class AuthService {
        public UserRepository $repository;

        // 생성자에서 DB 호출
        public function __construct() {
            $this->repository = new UserRepository();
        }

        // 회원가입 로직
        public function registerServices(array $data) {
            // 데이터 꺼내기
            $email = $data['email'];
            $password = $data['password'];
            $nickname = $data['nickname'];

            // 비밀번호 해쉬화
            $pwdHash = Password::passwordHash($password);

            // 이메일 정규화
            $emailNorm = strtolower($email);
            
            // DB 실행
            $result = $this->repository->registerDB($emailNorm, $pwdHash, $nickname);

            return $result;
        }

        // 로그인
        public function loginServices($data) {
            // 데이터 확인
            $email = $data['email'];
            $pwd = $data['password'];

            // DB 실행
            $result = $this->repository->loginDB($email, $pwd);

            if (is_int($result)) { 
                $jwt = amw::tokenRequest($result);
                    return $jwt;
                }

            // jwt 외 에러 반환
            return $result;
        }
    }