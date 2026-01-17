<?php
    namespace Tripmate\Backend\Modules\Users\Repositories;

    use Tripmate\backend\Core\DB;

    class UsersReadRepository {
        public DB $db;
        public $pdo;

        // DB 연결
        public function __construct() {
            $this->db = new DB();

            // 함수 호출
            $this->pdo = $this->db->getConnection();
        }

        // 내 정보 조회
        public function userMyPageRepository($userId) {
            // 트레젝션 실행
            $this->pdo->beginTransaction();
            
            // 회원 조회
            $query = $this->pdo->prepare("SELECT user_id, email_norm AS email, name, created_at FROM Users WHERE user_id = ?;");
        
            // email 값 넣기
            if(!$query->execute([$userId])) {
                $this->pdo->rollback();
                return "DB_EXCEPTION";
            } 

            // 값 가져오기
            $data = $query->fetch();

            if (!$data) {
                return "USER_NOT_FOUND"; 
            }

            $this->pdo->commit();

            $email = $data['email'];
            $nickname = $data['name'];
            $createdAt = $data['created_at'];
        
            return ["email" => $email, "nickname" => $nickname, "created_at" => $createdAt];
        }

        // 회원 탈퇴
        public function userSecessionRepository($userId) {
            // 트레젝션 실행
            $this->pdo->beginTransaction();

            // 쿼리 문 작성
            $query = $this->pdo->prepare("DELETE FROM Users WHERE user_id=?;");

            if (!$query->execute([$userId])) {
                $this->pdo->rollback();
                return "DB_EXCEPTION";
            }

            $this->pdo->commit();

            return "SUCCESS";
    }
}