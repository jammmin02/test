<?php
    namespace Tripmate\Backend\Modules\Places\Repositories;

    use Tripmate\Backend\Core\DB;
    use PDO;

    class PlacesRepository {
        public DB $db;
        public $pdo;

        public function __construct() {
            // DB 객체 생성
            $this->db = new DB();

            // 함수 호출
            $this->pdo = $this->db->getConnection();
        }

        // upsert db 로직
        public function upsertRepository($name, $category, $address, $externalRef, $lat, $lng) {
            // 트레젝션
            $this->pdo->beginTransaction();

            // category_name → category_id 매핑
            $categoryResult = $this->pdo->prepare("SELECT category_id FROM PlaceCategory WHERE name = ?");

            // error
            if (!$categoryResult->execute([$category])) {
                $this->pdo->rollback();
                return "CATEGORY_FAIL";
            }

            // category_id 값 확인
            $data = $categoryResult->fetch();

            if (!$data) {
                $this->pdo->rollback();
                return "CATEGORY_FAIL";
            }

            $categoryId = $data['category_id'];

            // Place 테이블에 저장 (external_ref 기준 없을 시)
            $placeResult = $this->pdo->prepare("INSERT INTO Place(category_id, name, address, lat, lng, external_ref)
                                SELECT :cid, :name, :addr, :lat, :lng, :ext
                                FROM DUAL
                                WHERE NOT EXISTS (SELECT 1 FROM Place WHERE external_ref = :ext)");

            // error
            if (!$placeResult->execute(['cid' => $categoryId, 'name' => $name, 'addr' => $address, 'lat' => $lat, 'lng' => $lng, 'ext' =>$externalRef])) {
                $this->pdo->rollback();
                return "PLACE_FAIL";
            }

            // 테이블 레코드 조회
            $dataResult = $this->pdo->prepare("SELECT place_id, category_id, name, address, lat, lng, external_ref FROM Place WHERE external_ref = ?;");
            
            if (!$dataResult->execute([$externalRef])) {
                $this->pdo->rollback();
                return "PLACE_FAIL";
            }

            // 반환
            $totalData = $dataResult->fetch(\PDO::FETCH_ASSOC);

            if (!$totalData) {
                $this->pdo->rollback();
                return "PLACE_FAIL";
            }

            $this->pdo->commit();

            return $totalData;
        }

        // 장소 단건 조회
        public function placeRepository($placeId) {
            // 트레젝션
            $this->pdo->beginTransaction();

            // place 조회
            $categoryResult = $this->pdo->prepare("SELECT p.place_id, p.name, p.address, p.lat, p.lng, pc.name AS category 
                                FROM Place p 
                                LEFT JOIN PlaceCategory pc ON p.category_id=pc.category_id 
                                WHERE p.place_id = ?;");

            
            if(!$categoryResult->execute([$placeId])) {
                $this->pdo->rollback();
                return "PLACE_FAIL";
            } 

            // 값 꺼내기
            $data = $categoryResult->fetch($this->pdo::FETCH_ASSOC);

            if(!$data) {
                $this->pdo->rollback();
                return "PLACE_FAIL";
            }

            $this->pdo->commit();

            return $data; 
        }
    }