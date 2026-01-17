<?php
    namespace Tripmate\Backend\Modules\Regions\Repositories;

    use Tripmate\Backend\Core\DB;

    // DB 로직 작성
    class RegionsRepository {
        public DB $db;
        public $pdo;

        public function __construct() {
            // DB 객체 생성
            $this->db = new DB();

            // 함수 호출
            $this->pdo = $this->db->getConnection();
        }

        // 지역 조회
        public function regionRepository($query) {
            // 트레젝션
            $this->pdo->beginTransaction();

            // DB 조회
            $region = $this->pdo->prepare("SELECT region_id, name, country_code
                            FROM Region
                            WHERE name LIKE CONCAT('%', ?, '%');");

            if (!$region->execute([$query])) {
                $this->pdo->rollback();
                return "DB_EXCEPTION";
            }

            // 값 확인
            $result = $region->fetchAll($this->pdo::FETCH_ASSOC);

            // 결과 값이 하나 이상인 경우
            if(count($result) > 0) {
                $arr = [];

                foreach($result as $row) {
                    $arr[] = [
                        'item' => [
                            'region_id' => $row['region_id'],
                            'name' => $row['name'],
                            'country_code' => $row['country_code']
                        ]
                    ];
                }

                // 결과값 반환
                return $arr;
            }
        }
    }