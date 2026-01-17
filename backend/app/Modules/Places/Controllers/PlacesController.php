<?php
    namespace Tripmate\Backend\Modules\Places\Controllers;

    use Tripmate\Backend\Core\Controller;
    use Tripmate\Backend\Core\Validator;
    use Tripmate\Backend\Common\Middleware\AuthMiddleware as amw;
    use Tripmate\Backend\Modules\Places\Services\PlacesService;

    class PlacesController extends Controller {
        public Validator $validator;
        public PlacesService $service;

        public function __construct($request, $response) {
            parent::__construct($request, $response);

            // 유효성 검증
            $this->validator = new Validator();

            $this->service = new PlacesService();
        }
        
        // 더미데이터 기반 장소 검색
        public function search() {
            // query, page
            $query = $this->request->query;

            // 데이터 검증
            $result = $this->validator->validatePlace($query);

            if ($result !== true) {
                $this->error("AUTH_FAILED", "입력값이 유효하지 않습니다. 다시 한 번 확인해주세요.");
                exit;
            } 
            
            // 서비스 호출
            $place = $this->service->searchService($query);
            
            $this->success($place);
        }

        // 사용자가 선택한 외부 결과 중 하나 내부 저장
        public function placeUpsert() {
            // 토큰 검증
            $userId = amw::tokenResponse($this->request);

            // 유효성 검증
            $data = $this->request->body;
            if ($this->validator->validatePlaceCategory($data) !== true) {
                $this->error("AUTH_FAILED", "입력값이 유효하지 않습니다. 다시 한 번 확인해주세요.");
                exit;
            }

            // 서비스 전달
            $place = $this->service->upsertService($data);

            if ($place == "CATEGORY_FAIL") {
                $this->error($place, "카테고리 정보 처리에 실패했습니다.");
            } else if($place == "PLACE_FAIL") {
                $this->error($place, "장소 정보 처리에 실패했습니다.");
            } else {
                $this->success($place);
            }
        }

        // 단건 조회
        public function singlePlaceSearch(int $placeId) {
            
            // 서비스 전달
            $result = $this->service->singlePlaceService($placeId);
        
            if($result == "PLACE_FAIL") {
                $this->error($result, "장소 정보 처리에 실패했습니다.");
            } else {
                $this->success($result);
            }
        }
    } 