<?php

// 1. use 작성 (Tripmate\Backend\ )
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Modules\Trips\Controllers\TripsController;

// 2. 콜백을 위한 익명함수 작성
return function(\AltoRouter $router, Request $request, Response $response) {
    
  // 2-1. 여행 생성 : POST/api/v1/trips
  $router->map(
    'POST',
    '/api/v1/trips',
    [new TripsController($request, $response), 'createTrip']
  );
  // 2-2. 여행 목록 조회 : GET/api/v1/trips
  $router->map(
    'GET',
    '/api/v1/trips',
    [new TripsController($request, $response), 'getTrips']
  );

  // 2-3. 여행 딘건 조회 : GET /api/v1/trips/{trip_id}
  $router->map(
    'GET',
    '/api/v1/trips/[i:trip_id]',
    [new TripsController($request, $response), 'showTrip']
  );
  // 2-4. 여행 수정 : PUT /api/v1/trips/{trip_id}
  $router->map(
    'PUT',
    '/api/v1/trips/[i:trip_id]',
    [new TripsController($request, $response), 'updateTrip']
  );
  // 2-5. 여행 삭제 : DELETE /api/v1/trips/{trip_id}
  $router->map(
    'DELETE',
    '/api/v1/trips/[i:trip_id]',
    [new TripsController($request, $response), 'deleteTrip']
  );
};