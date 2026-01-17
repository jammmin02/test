<?php
    namespace Tripmate\Backend\Modules\Places\Services;

    use Tripmate\Backend\Modules\Places\Services\DummyPlaces;
    use Tripmate\Backend\Modules\Places\Repositories\PlacesRepository;

    class PlacesService {
        public PlacesRepository $repository;

        public function __construct() {
            $this->repository = new PlacesRepository();
        }

        // 더미데이터 장소 검색
        public function searchService($query) {
            // 데이터 꺼내기
            $place = $query['place'];

            // 반환된 2차원 더미데이터 배열
            $result = DummyPlaces::getPlaces($place);
            
            // 페이지당 개수
            $pageMaxItem = 5;

            // 보여줄 페이지 개수
            $maxPageTotal = 5;

            // 현재 페이지
            if (isset($query['page'])) {
                $page = $query['page'];
            } else {
                $page = 1;
            }

            // 시작 아이템 인덱스
            $startIndex = ($page - 1) * $pageMaxItem; 

            // 데이터 슬라이싱
            $paginatedData = array_slice($result, $startIndex, $pageMaxItem);

            // 전체 페이지 수 = 아이템 전 개수 / 페이지 당 아이템 수
            $totalPage = ceil(count($result) / $pageMaxItem);
            
            // 시작페이지 및 끝 페이지
            $startPage = (floor($page - 1) / $maxPageTotal) * $maxPageTotal + 1;
            $endPage = $startPage + ($maxPageTotal - 1);
                
            // 페이지 범위 조정
            if ($endPage > $totalPage) {
                $endPage = $totalPage;
            } else if ($page < 1) {
                $page = 1;
            }
            
            // 페이지 관련 정보와 데이터 반환
            return [
                "meta" => [
                    'page' => $page,
                    'page_max_item' => $pageMaxItem,
                    'total_page' => $totalPage,
                    'start_page' => $startPage, 
                    'end_page' => $endPage,
                    'max_item' => count($result)
                ],
                "data" => $paginatedData
                ];
        }
        
        // 외부 결과 중 하나를 내부로 저장
        public function upsertService($data) {
            // data값 꺼내기
            $name = $data['name'];
            $category = $data['category'];
            $address = $data['address'];
            $externalRef = $data['external_ref'];
            $lat = $data['lat'];
            $lng = $data['lng'];
            
            // DB 전달
            $result = $this->repository->upsertRepository($name, $category, $address, $externalRef, $lat, $lng);

            return $result;
        }

        // 장소 단건 조회
        public function singlePlaceService($placeId) {
            // db 전달
            $result = $this->repository->placeRepository($placeId);

            return $result;
        }
    }