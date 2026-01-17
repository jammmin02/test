<?php
    namespace Tripmate\Backend\Modules\Auth\Repositories;

    use Tripmate\Backend\Core\DB;
    use Tripmate\Backend\Common\Utils\Password;

    // DB 로직 작성
    class UserRepository {
        public DB $db;
        public $pdo;

        public function __construct() {
            // DB 객체 생성
            $this->db = new DB();

            // 함수 호출
            $this->pdo = $this->db->getConnection();
        }

        // 회원가입 로직
        public function registerDB($email, $pwdHash, $nickname) {
            // 트레젝션
            $this->pdo->beginTransaction();

            // email 중복 검사
            $result = $this->pdo->prepare("SELECT email_norm FROM Users WHERE email_norm = :email");
            if (!$result->execute(['email' => $email])) {
                $this->pdo->rollback();
                return "DB_EXCEPTION";
            }

            // 반환 값이 있는지 확인
            if ($result->fetch()) {
                $this->pdo->rollback();
                return "DUPLICATE_EMAIL";
            } 

            // 중복 값이 없을 경우 값 넣기
            $insert = $this->pdo->prepare("INSERT INTO Users (email_norm, password_hash, name) VALUES (:email_norm, :password_hash, :name);");
            
            if ($insert->execute(['email_norm' => $email, 'password_hash' => $pwdHash, 'name' => $nickname])) {
                // DB 처리 완료
                $this->pdo->commit();

                // 성공 반환
                return "REGISTER_SUCCESS";

            } else {
                $this->pdo->rollback();
                return "DB_ERROR";
            }
        }
        // 로그인 로직
        public function loginDB($email, $pwd) {
            // 트레젝션 실행
            $this->pdo->beginTransaction();

            // 이메일을 이용해 조회
            $query = $this->pdo->prepare("SELECT user_id, password_hash FROM Users WHERE email_norm = ?;");
            
            if(!$query->execute([$email])) {
                $this->pdo->rollback();
                return "DB_EXCEPTION";
            }

            // 조회하기
            $data = $query->fetch();

            // 이메일 조회 반환 값이 없을 경우
            if(!$data) {
                $this->pdo->rollback();
                return "AUTH_FAILED";
            }

            // 조회한 데이터 꺼내기
            $userId = $data['user_id'];
            $pwdHash = $data['password_hash'];

            $this->pdo->commit();

            // 비밀번호 검증
            if(Password::passwordValdataion($pwd, $pwdHash)) {
                // JWT 발급
                return $userId;
            } else {
                // 비밀번호가 알맞지 않을 경우
                return "AUTH_FAILED";
            }
        }
    }