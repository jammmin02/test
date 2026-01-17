<?php
// 1. namespace 작성
namespace Tripmate\Backend\Modules\ScheduleItems\Controllers;

// 2. use 작성
use Tripmate\Backend\Common\Middleware\AuthMiddleware;
use Tripmate\Backend\Core\Controller;
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Modules\ScheduleItems\Services\ScheduleItemsService;
use Tripmate\Backend\Core\Validator;

// 3. ScheduleItemsController 클래스 정의
class ScheduleItemsController extends Controller {
  // 4. 프러퍼티 정의
  public ScheduleItemsService $scheduleItemsService;
  public Validator $validator;

  // 5. 생성자에서 Request, Response, ScheduleItemsService 초기화 
  public function __construct(Request $request, Response $response) {
    // 5-1. 부모 생성자 호출
    parent::__construct($request, $response);
    // 5-2. ScheduleItemsService 인스턴스 생성
    $this->scheduleItemsService = new ScheduleItemsService();
    // 5-3. Validator 인스턴스 생성
    $this->validator = new Validator();
  }

  // 1. 일정 생성 : POST /api/v1/trips/{trip_id}/days/{day_no}/items
  public function createScheduleItem(int $tripId, int $dayNo) {

    // 1-1. trip_id가 없으면 400 반환
    if (empty($tripId) || $tripId <= 0) {
      return $this->response->error('MISSING_TRIP_ID', 'trip_id가 필요합니다.', 400);
    }
    // 1-2. day_no가 없으면 400 반환
    if (empty($dayNo) || $dayNo <= 0) {
      return $this->response->error('MISSING_DAY_NO', 'day_no가 필요합니다.', 400);
    }

    // 1-3. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error
    // 1-4. 유효하지 않은 토큰일 시 에러 응답
    if (!$userId) {
        return $this->response->error('UNAUTHORIZED', '유효하지 않은 토큰입니다.', 401);
    }

    // 1-5. 유효성 검증
    $body = $this->request->body ?? [];
    // $validation = $this->validator->validateRelocation($body);
    // // 1-6. 유효성 검증 실패 시 에러 응답
    // if ($validation !== true) {
    //   return $this->response->error('VALIDATION_ERROR', $validation, 400);
    // }

    // 1-7. memo, visit_time, place_id 추출
    $placeId = $body['place_id'] ?? null;
    $visitTime = $body['visit_time'] ?? null;
    $memo = $body['memo'] ?? null;

    // 1-8. 일정 생성 서비스 호출
    $itemId = $this->scheduleItemsService->createScheduleItem(
      (int)$userId, 
      (int)$tripId, 
      (int)$dayNo, 
      $placeId, 
      $visitTime, 
      $memo);

    // 1-9. 일정 생성 실패 시 에러 응답
    if ($itemId === false) {
      return $this->response->error('CREATE_SCHEDULE_ITEM_FAILED', '일정 생성에 실패했습니다.', 500);
    }

    // 1-10. 일정 생성 성공 시 응답 반환
    return $this->response->success(['item_id' => $itemId],  201);
  }

  // 2. 일정 목록 조회 : GET /api/v1/trips/{trip_id}/days/{day_no}/items
  public function getScheduleItems(int $tripId, int $dayNo) {

    // 2-1. 유효성 검증(trip_id)
    $validationTripId = $this->validator->validateTripId($tripId);
    if ($validationTripId !== true) {
      return $this->response->error('MISSING_TRIP_ID', 'trip_id가 필요합니다.', 400);
    }
    // 2-2. 유효성 검증(day_no)
    $validationDayId = $this->validator->validateDayNo($dayNo);
    if ($validationDayId !== true) {
      return $this->response->error('MISSING_DAY_NO', 'day_no가 필요합니다.', 400);
    }

    // 2-3. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error
    // 2-4. 유효하지 않은 토큰일 시 에러 응답
    if (!$userId) {
        return $this->response->error('UNAUTHORIZED', '유효하지 않은 토큰입니다.', 401);
    }

    // 2-5. 일정 목록 조회 서비스 호출
    $items = $this->scheduleItemsService->getScheduleItems(
      (int)$userId, 
      (int)$tripId, 
      (int)$dayNo
    );

    // 2-6. 일정 목록 조회 실패 시 에러 응답
    if ($items === false) {
      return $this->response->error('GET_SCHEDULE_ITEMS_FAILED', '일정 목록 조회에 실패했습니다.', 500);
    }

    // 2-7. 일정 목록 조회 성공 시 응답 반환
    return $this->response->success(['items' => $items],  200);
  }

  // 3. 일정 부분 수정
  // PATCH /api/v1/trips/{trip_id}/days/{day_no}/items/{item_id}
  public function updateScheduleItem(int $tripId, int $dayNo, int $itemId) {
    // 3-1. 유효성 검증(trip_id)
    $validationTripId = $this->validator->validateTripId($tripId);
    if ($validationTripId !== true) {
      return $this->response->error('MISSING_TRIP_ID', 'trip_id가 필요합니다.', 400);
    }
    // 3-2. 유효성 검증(day_no)
    $validationDayId = $this->validator->validateDayNo($dayNo);
    if ($validationDayId !== true) {
      return $this->response->error('MISSING_DAY_NO', 'day_no가 필요합니다.', 400);
    }
    // 3-3. 유효성 검증(item_id)
    $validationItemId = $this->validator->validateItemId($itemId);
    if ($validationItemId !== true) {
      return $this->response->error('MISSING_ITEM_ID', 'item_id가 필요합니다.', 400);
    }

    // 3-4. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error
    // 3-5. 유효하지 않은 토큰일 시 에러 응답
    if (!$userId) {
        return $this->response->error('UNAUTHORIZED', '유효하지 않은 토큰입니다.', 401);
    }

    // 3-6. memo, visit_time 추출
    $body = $this->request->body ?? [];
    $visitTime = $body['visit_time'] ?? null;
    $memo = $body['memo'] ?? null;

    // 3-7. 일정 부분 수정 서비스 호출
    $items = $this->scheduleItemsService->updateScheduleItem(
      (int)$userId, 
      (int)$tripId, 
      (int)$itemId, 
      (int)$dayNo, 
      $visitTime, 
      $memo
    );

    // 3-8. 일정 부분 수정 실패 시 에러 응답
    if (!$items) {
      return $this->response->error('UPDATE_SCHEDULE_ITEM_FAILED', '일정 부분 수정에 실패했습니다.', 500);
    }

    // 3-9. 일정 부분 수정 성공 시 응답 반환
    return $this->response->success(['items' => $items],  200);
  } 

  // 4. 일정 삭제 
  // DELETE /api/v1/trips/{trip_id}/days/{day_no}/items/{item_id}
  public function deleteScheduleItem(int $tripId, int $dayNo, int $itemId) {
    // 4-1. 유효성 검증(trip_id)
    $validationTripId = $this->validator->validateTripId($tripId);
    if ($validationTripId !== true) {
      return $this->response->error('MISSING_TRIP_ID', 'trip_id가 필요합니다.', 400);
    }
    // 4-2. 유효성 검증(day_no)
    $validationDayId = $this->validator->validateDayNo($dayNo);
    if ($validationDayId !== true) {
      return $this->response->error('MISSING_DAY_NO', 'day_no가 필요합니다.', 400);
    }
    // 4-3. 유효성 검증(item_id)
    $validationItemId = $this->validator->validateItemId($itemId);
    if ($validationItemId !== true) {
      return $this->response->error('MISSING_ITEM_ID', 'item_id가 필요합니다.', 400);
    }

    // 4-4. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error
    // 4-5. 유효하지 않은 토큰일 시 에러 응답
    if (!$userId) {
        return $this->response->error('UNAUTHORIZED', '유효하지 않은 토큰입니다.', 401);
    }

    // 4-6. 일정 삭제 서비스 호출
    $deleted = $this->scheduleItemsService->deleteScheduleItem(
      (int)$userId, 
      (int)$tripId, 
      (int)$dayNo, 
      (int)$itemId
    );

    // 4-7. 일정 삭제 실패 시 에러 응답
    if ($deleted === false) {
      return $this->response->error('DELETE_SCHEDULE_ITEM_FAILED', '일정 삭제에 실패했습니다.', 500);
    }

    // 4-8. 일정 삭제 성공 시 응답 반환
    return $this->response->success(['deleted' => $deleted], 200);
  }

  // 5. 일정 재배치
  // POST /api/v1/trips/{trip_id}/days/{day_no}/items:reorder
  public function reorderSingleScheduleItem(int $tripId, int $dayNo) {
    // 5-1. 유효성 검증(trip_id)
    $validationTripId = $this->validator->validateTripId($tripId);
    if ($validationTripId !== true) {
      return $this->response->error('MISSING_TRIP_ID', 'trip_id가 필요합니다.', 400);
    }
    // 5-2. 유효성 검증(day_no)
    $validationDayId = $this->validator->validateDayNo($dayNo);
    if ($validationDayId !== true) {
      return $this->response->error('MISSING_DAY_NO', 'day_no가 필요합니다.', 400);
    }

    // 5-3. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error
    // 5-4. 유효하지 않은 토큰일 시 에러 응답
    if (!$userId) {
        return $this->response->error('UNAUTHORIZED', '유효하지 않은 토큰입니다.', 401);
    }

    // 5-5. 요청 데이터에서 item_ids 및 new_seq_no 추출 없으면 0
    $body = $this->request->body ?? [];
    $itemId = isset($body['item_id']) ? (int)$body['item_id'] : 0;
    $newSeqNo = isset($body['new_seq_no']) ? (int)$body['new_seq_no'] : 0;
    error_log("Reorder Request - UserID: $userId, TripID: $tripId, DayNo: $dayNo, ItemID: $itemId, NewSeqNo: $newSeqNo");

    // 5-6. 일정 재배치 서비스 호출
    $reordered = $this->scheduleItemsService->reorderSingleScheduleItem(
      (int)$userId, 
      (int)$tripId, 
      (int)$dayNo, 
      $itemId,
      $newSeqNo

    );

    // 5-7. 일정 재배치 실패 시 에러 응답
    if ($reordered === false) {
      return $this->response->error('REORDER_SCHEDULE_ITEMS_FAILED', '일정 재배치에 실패했습니다.', 500);
    }

    // 5-8. 일정 재배치 성공 시 응답 반환
    return $this->response->success(['reordered' => $reordered],  200);
  }

}