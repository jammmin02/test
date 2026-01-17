<?php
    namespace Tripmate\Backend\Modules\Regions\Services;

    use Tripmate\Backend\Core\DB;

    class RegionsSeeder {
        public static function Category() {
            $db = new DB();

            // db 연결
            $pdo = $db->getConnection();

            // 지역명 배열
            $regions = [
                        '서울특별시',
                        '부산광역시',
                        '대구광역시',
                        '인천광역시',
                        '광주광역시',
                        '대전광역시',
                        '울산광역시',
                        '세종특별자치시',
                        '경기도',
                        '강원특별자치도',
                        '충청북도',
                        '충청남도',
                        '전북특별자치도',
                        '전라남도',
                        '경상북도',
                        '경상남도',
                        '제주특별자치도',
                    ];

            // db 값 추가
            foreach ($regions as $cat) {
                $query = $pdo->prepare("INSERT INTO Region (name, country_code) VALUES (:region, :country)");
                $query->execute(['region' => $cat, 'country' => 'KR']);
            }
        }
    }