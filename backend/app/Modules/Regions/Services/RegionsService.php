<?php
    namespace Tripmate\Backend\Modules\Regions\Services;

    use Tripmate\Backend\Modules\Regions\Repositories\RegionsRepository;

    // 서비스 로직
    class RegionsService {
        public RegionsRepository $repository;

        // 생성자에서 DB 호출
        public function __construct() {
            $this->repository = new RegionsRepository();
        }

        // 지역 조회
        public function regionService($query) {
            // db 전달
            $result = $this->repository->regionRepository($query);
            
            return $result;
        }
    }