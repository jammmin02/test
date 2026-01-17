<?php
// 1. use 작성
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Modules\ScheduleItems\Controllers\ScheduleItemsController;

// 2. 콜백을 위한 익명함수 작성
return function(\AltoRouter $router, Request $request, Response $response) {
    
  // 2-1. 일정 생성 : POST /api/v1/trips/{trip_id}/days/{day_no}/items
  $router->map(
    'POST',
    '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items',
    [new ScheduleItemsController($request, $response), 'createScheduleItem']
  );

  // 2-2. 일정 목록 조회 : GET /api/v1/trips/{trip_id}/days/{day_no}/items
  $router->map(
    'GET',
    '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items',
    [new ScheduleItemsController($request, $response), 'getScheduleItems']
  );

  // 2-3. 일정 수정 : PATCH /api/v1/trips/{trip_id}/days/{day_no}/items/{item_id}
  $router->map(
    'PATCH',
    '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items/[i:item_id]',
    [new ScheduleItemsController($request, $response), 'updateScheduleItem']
  );

  // 2-4. 일정 삭제 : DELETE /api/v1/trips/{trip_id}/days/{day_no}/items/{item_id}
  $router->map(
    'DELETE',
    '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items/[i:item_id]',
    [new ScheduleItemsController($request, $response), 'deleteScheduleItem']
  );

  // 2-5. 일정 재배치 : POST /api/v1/trips/{trip_id}/days/{day_no}/items:reorder
  $router->map(
    'POST',
    '/api/v1/trips/[i:trip_id]/days/[i:day_no]/items:reorder',
    [new ScheduleItemsController($request, $response), 'reorderSingleScheduleItem']
  );
};
