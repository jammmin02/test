<?php
// namespace 작성
namespace Tripmate\Backend\Modules\TripDays\Controllers;

// use 작성
use Tripmate\Backend\Common\Middleware\AuthMiddleware;
use Tripmate\Backend\Core\Controller;
use Tripmate\Backend\Modules\TripDays\Services\TripDaysService;
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Core\Validator;

// TripDaysController 클래스 작성
class TripDaysController extends Controller { 

  // 1. 프로퍼티 정의
  public TripDaysService $tripDaysService;
  public Validator $validator;

  // 2. 생성자에서 Request, Response, TripDaysService, Validator 초기화
  function __construct(Request $request, Response $response) {
    // 2-1. 부모 생성자 호출
    parent::__construct($request, $response);
    // 2-2. TripDaysService 인스턴스 생성
    $this->tripDaysService = new TripDaysService();
    // 2-3. Validator 인스턴스 생성
    $this->validator = new Validator();
  }

  // 3. trip day 생성 : POST /api/v1/trips/{trip_id}/days
  // 3-1. createTripDay 메서드 정의
  public function createTripDay(int $tripId) {

    // 3-2. trip_id가 없으면 에러 응답
    if (!$tripId) {
        return $this->response->error('MISSING_TRIP_ID', 'trip_id가 필요합니다.', 400);
    }

    // 3-3. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error
    // 3-4. 유효하지 않은 토큰일 시 에러 응답
    if (!$userId) {
        return $this->response->error('UNAUTHORIZED', '유효하지 않은 토큰입니다.', 401);
    }

    // 3-5. 유효성 검증
    $body = $this->request->body ?? [];
    $validation = $this->validator->validateDays($body);

    // 3-6. memo 추출
    $memo = $body['memo'] ?? null;

    // 3-7. 검증 실패시 에러 응답
    if ($validation !== true) {
        return $this->response->error('VALIDATION_ERROR', $validation, 422);
    }
    
    // 3-8. TripDaysService의 addTripDay 호출
    $tripDayId = $this->tripDaysService->addTripDay(
        (int)$userId,
        (int)$tripId,
        (int)$body['day_no'],
        $memo 
    );

    // 3-9. 실패 시 응답
    if ($tripDayId === false) {
        return $this->response->error('CREATION_FAILED', '여행 일자 생성에 실패했습니다.', 500);
    }

    // 3-10. 성공 시 응답 (생성된 trip_day_id 반환)
    return $this->response->success(
        ['trip_day_id' => $tripDayId],
        201
    );

   }

  // 4. trip day 단건 조회 : GET /api/v1/trips/{trip_id}/days/{day_no}
  // 4-1. showTripDay 메서드 정의
  public function showTripDay($tripId, $dayNo) {
    // 4-2. trip_id 또는 day_no가 없으면 에러 응답
    if (!$tripId || !$dayNo) {
        return $this->response->error('MISSING_PARAMETERS', 'trip_id와 day_no가 필요합니다.', 400);
    }

    // 4-3. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error
    // 4-4. 유효하지 않은 토큰일 시 에러 응답
    if (!$userId) {
        return $this->response->error('UNAUTHORIZED', '유효하지 않은 토큰입니다.', 401);
    }

    // 4-5. TripDaysService의 getTripDay 호출
    $tripDay = $this->tripDaysService->getTripDay(
      (int)$userId,
      (int)$tripId,
      (int)$dayNo
    );

    // 4-6. 실패 시 응답
    if ($tripDay === false) {
        return $this->response->error('NOT_FOUND', '해당 여행 일자를 찾을 수 없습니다.', 404);
      }
    
    // 4-7. 성공 시 응답 (trip day 정보 반환)
    return $this->response->success(
        ['trip_day' => $tripDay],
        200
    );

  }

    // 5. trip day 목록 조회 : GET /api/v1/trips/{trip_id}/days
    // 5-1. getTripDays 메서드 정의
    // 일차 목록 조회
    public function getTripDays(int $tripId) {
        // 토큰 검증
        $userId = AuthMiddleware::tokenResponse($this->request);

        // 서비스 전달
        $result = $this->tripDaysService->daysListService($tripId, $userId);
    
        if ($result == "DB_SELECT_FAILD") {
            $this->error($result, "DB 조회에 실패하였습니다.");
        } else {
            $this->success([$result]);
        }
    }

    // 6. trip day 수정 : PUT /api/v1/trips/{trip_id}/days/{day_no}
    // 6-1. updateTripDay 메서드 정의
    // 일차 메모 속성 수정
    public function updateTripDay(int $tripId, int $dayId) {
        // 토큰 검증
        $userId = AuthMiddleware::tokenResponse($this->request);
    
        // 데이터 가져오기
        $data = $this->request->body;

        // 유효성 검증
        if ($this->validator->validateMemo($data) !== true) {
            $this->error("VALIDATION_ERROR", "입력값이 유효하지 않습니다.");
        }

        $memo = $data['memo'];

        $result = $this->tripDaysService->noteService($tripId, $dayId, $memo, $userId);
    
        if($result == "UPDATE_FAIL") {
            $this->error($result, "메모 업데이트에 실패했습니다.");
        } else if ($result == "NOT_FOUND") {
            $this->error($result, "입력값 조회에 실패하였습니다.");
        } else {
            $this->success($result);
        }
    }

  // 7. trip day 삭제 : DELETE /api/v1/trips/{trip_id}/days/{day_no}
  // 7-1. deleteTripDay 메서드 정의
  public function deleteTripDay(int $tripId, int $dayNo) {
    // 7-2. trip_id 또는 day_no가 없으면 에러 응답
    if (!$tripId || !$dayNo) {
        return $this->response->error('MISSING_PARAMETERS', 'trip_id와 day_no가 필요합니다.', 400);
    }

    // 7-3. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error
    // 7-4. 유효하지 않은 토큰일 시 에러 응답
    if (!$userId) {
        return $this->response->error('UNAUTHORIZED', '유효하지 않은 토큰입니다.', 401);
    }

    // 7-5. TripDaysService의 deleteTripDay 호출
    $deleted = $this->tripDaysService->deleteTripDay(
      (int)$userId,
      (int)$tripId,
      (int)$dayNo
    );

    // 7-6. 실패 시 응답
    if ($deleted === false) {
        return $this->response->error('DELETION_FAILED', '여행 일자 삭제에 실패했습니다.', 500);
      }
    
    // 7-7. 성공 시 응답
    return $this->response->success(
        ['message' => '여행 일자가 성공적으로 삭제되었습니다.'],
        200
    );
  }

    // 8. trip day 순서 변경 : POST /api/v1/trips/{trip_id}/days:reorder
    // 8-1. reorderTripDays 메서드 정의
    // 일차 재배치
    public function reorderTripDays(int $tripId) {
        // 토큰 검증
        $userId = AuthMiddleware::tokenResponse($this->request);

        // 본문 데이터 꺼내기
        $data = $this->request->body;

        // 유효성 검증
        if (!$this->validator->validateDayRelocation($data)) {
            $this->error("VALIDATION_ERROR", "입력값이 유효하지 않습니다.");
        }
        $orders = $data['orders'];

        // 서비스 전달
        $result = $this->tripDaysService->relocationDaysService($tripId, $orders, $userId);
    
        if($result == "SELECT_FAIL") {
            $this->error($result, "값 조회에 실패했습니다.");
        } else if($result == "UPDATE_FAIL") {
            $this->error($result, "일차 재배치에 실패했습니다.");
        } else if ($result == "NOT_FOUND") {
            $this->error($result, "값 조회에 실패했습니다.");
        } else {
            $this->success($result);
        }
    }
}
