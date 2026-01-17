<?php
// 1. use 작성
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Modules\TripDays\Controllers\TripDaysController;

// 2. 콜백을 위한 익명함수 작성
return function(\AltoRouter $router, Request $request, Response $response){

  // 2-1. trip day 생성 : POST /api/v1/trips/{trip_id}/days
  $router->map(
    'POST',
    '/api/v1/trips/[i:trip_id]/days',
    [new TripDaysController($request, $response), 'createTripDay']
  );

  // 2-2 trip day 목록 조회 : GET /api/v1/trips/{trip_id}/days
  $router->map(
    'GET',
    '/api/v1/trips/[i:trip_id]/days',
    [new TripDaysController($request, $response), 'getTripDays']
  );

  // 2-3. trip day 단건 조회 : GET /api/v1/trips/{trip_id}/days/{day_no}
  $router->map(
    'GET',
    '/api/v1/trips/[i:trip_id]/days/[i:day_no]',
    [new TripDaysController($request, $response), 'showTripDay']
  );

  // 2-4. trip day 수정 : PUT /api/v1/trips/{trip_id}/days/{day_no}
  $router->map(
    'PUT',
    '/api/v1/trips/[i:trip_id]/days/[i:day_no]',
    [new TripDaysController($request, $response), 'updateTripDay']
  );

  // 2-5. trip day 삭제 : DELETE /api/v1/trips/{trip_id}/days/{day_no}
  $router->map(
    'DELETE',
    '/api/v1/trips/[i:trip_id]/days/[i:day_no]',
    [new TripDaysController($request, $response), 'deleteTripDay']
  );

  // 2-6. trip day 순서 재배치 : POST /api/v1/trips/{trip_id}/days:reorder
  $router->map(
    'POST',
    '/api/v1/trips/[i:trip_id]/days:reorder',
    [new TripDaysController($request, $response), 'reorderTripDays']
  );
};