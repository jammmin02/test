<?php
    namespace Tripmate\Backend\Modules\Regions\Controllers;

    use Tripmate\Backend\Core\Controller;
    use Tripmate\Backend\Modules\Regions\Services\RegionsService;
    use Tripmate\Backend\Core\Validator;

    class RegionsController extends Controller{
        public RegionsService $service;
        public Validator $validator;

        // 생성자
        public function __construct($request, $response) {
            // 부모 컨트롤러 생성자
            parent::__construct($request, $response);
            
            // 서비스 생성자
            $this->service = new RegionsService();

            // 유효성 검증
            $this->validator = new Validator();
        }

        // 지역 검색
        public function regionSearch() {
            // 데이터 꺼내기
            $query = $this->request->query['query'];

            // 유효성 검증
            $result = $this->validator->validateRegionSearch($query);

            if($result === false) {
                $this->error($result, "지역 값이 존재하지 않거나 알맞지 않습니다.");
                exit;
            }

            // 서비스 호출
            $regionService = $this->service->regionService($query);
        
            // 출력
            if($regionService == "DB_EXCEPTION") {
                $this->error($regionService, "지역 조회에 실패하였습니다.");
            } else {
                $this->success($regionService);
            }
        }
    }