<?php
namespace Tripmate\Backend\Modules\Trips\Controllers;

use Tripmate\Backend\Common\Middleware\AuthMiddleware;
use Tripmate\Backend\Core\Controller;
use Tripmate\Backend\Modules\Trips\Services\TripsService;
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Core\Validator;

// 1. TripController 클래스 정의
class TripsController extends Controller {
  // 2. 프러퍼티 정의
  public TripsService $tripsService;
  public Validator $validator;

  // 3. 생성자에서 Request, Response, TripsService 초기화 
  public function __construct(Request $request, Response $response) {
    // 3-1. 부모 생성자 호출
    parent::__construct($request, $response);
    // 3-2. TripsService 인스턴스 생성
    $this->tripsService = new TripsService();
    // 3-3. Validator 인스턴스 생성
    $this->validator = new Validator();
  }

  // 4. 여행 생성 : POST /api/v1/trips
  // 4-1. createTrip 메서드 정의
  public function createTrip() {
    // 4-1. 요청 데이터 가져오기
    $body = $this->request->body ?? [];

    // 4-2. 유효성 검증
    $validationResult = $this->validator->validateTrip($body);
    if ($validationResult !== true) {
        return $this->response->error('VALIDATION_ERROR', $validationResult, 422);
    }

    // 4-3. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error

    // 4-4. TripsService의 createTrip 호출
    $tripId = $this->tripsService->createTrip(
        (int)$userId,
        (int)$body['region_id'],
        $body['title'],
        $body['start_date'],
        $body['end_date']
    );

    // 4-5. 실패 시 응답
    if ($tripId === false) {
        return $this->response->error('CREATION_FAILED', '여행 생성에 실패했습니다.', 500);
    }

    // 4-6. 성공 시 응답 (생성된 trip_id 반환)
    return $this->response->success(
        ['trip_id' => $tripId],
        201
    );
  }

  // 5. 여행 목록 조회 : GET /api/v1/trips -> 페이지네이션 적용
  // 5-1. getTrips 메서드 정의 
  public function getTrips() {
    // 5-1. 요청 쿼리 가져오기
    $query = $this->request->query ?? [];

    // 5-2. 페이지네이션 기본값 설정
    $page = isset($query['page']) && ctype_digit($query['page']) && (int)$query['page'] > 0 ? (int)$query['page'] : 1;
    $perPage = isset($query['per_page']) && ctype_digit($query['per_page']) && (int)$query['per_page'] > 0 ? (int)$query['per_page'] : 20;

    // 5-3. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error
    
    // 5-4. TripsService의 findTrips 호출
    $trips = $this->tripsService->findTrips($userId, $page, $perPage);

    // 5-5. 실패 시 응답
    if ($trips === false) {
        return $this->response->error('RETRIEVAL_FAILED', '여행 목록 조회에 실패했습니다.', 500);
    }

    // 5-6. 성공 시 응답 (여행 목록 및 페이지네이션 정보 반환)
    return $this->response->success([
        'data' => $trips['items'],
        'pagination' => [
            'page' =>  $trips['page'],
            'per_page' => $trips['per_page'],
            'total' => $trips['total'], 
            'total_pages' => $trips['total_pages'],
        ],
      ], 200);
    
   
  }

  // 6. 여행 단건 조회 : GET /api/v1/trips/{trip_id}
  // 6-1. showTrip 메서드 정의
  public function showTrip(int $tripId) {
    
    // 6-1. trip_id가 없으면 400 응답
    if ($tripId <= 0) {
      return $this->response->error('INVALID_TRIP_ID', '유효하지 않은 trip_id입니다.', 400);
    }
    // 6-2. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error

    // 6-2. TripsService의 findTripById 호출
     $trip = $this->tripsService->findTripById($tripId, (int)$userId);

    // 6-3. 조회 실패 시 404 응답
    if ($trip === false) {
      return $this->response->error('NOT_FOUND', '해당 trip_id의 여행을 찾을 수 없습니다.', 404);
    }
    // 6-4. 성공 시 여행 데이터 반환
    return $this->response->success($trip, 200);
  }

  // 7. 여행 수정 : PUT /api/v1/trips/{trip_id}
  // 7-1. updateTrip 메서드 정의
  public function updateTrip(int $tripId) {
    // 7-2. trip_id가 없으면 400 응답
    if ($tripId <= 0) {
      return $this->response->error('INVALID_TRIP_ID', '유효하지 않은 trip_id입니다.', 400);
    }

    // 7-3. 요청 데이터 가져오기
    $body = $this->request->body ?? [];

    // 7-4. 유효성 검증
    if (empty($body['region_id']) || empty($body['title']) || empty($body['start_date']) || empty($body['end_date'])) {
      // 7-5. 필수 값 누락 시 422 응답
      return $this->response->error('VALIDATION_ERROR', '필수 값이 누락되었습니다.', 422);
    }

    // 7-6. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error

    // 7-7. TripsService의 updateTrip 호출
     $updated = $this->tripsService->updateTrip(
        $userId,
        $tripId,
        (int)$body['region_id'],
        (string)$body['title'],
        (string)$body['start_date'],
        (string)$body['end_date']
    );

    // 7-8. 수정 실패 시 404 응답
    if ($updated === false) {
      return $this->response->error('UPDATE_FAILED', '여행 수정에 실패했습니다.', 404);
    }

    // 7-9. 성공 시 응답 (수정된 trip_id 반환)
    return $this->response->success(
      ['trip_id' => $tripId],
      200
    );
  }

  // 8. 여행 삭제 : DELETE /api/v1/trips/{trip_id}
  // 8-1. deleteTrip 메서드 정의
  public function deleteTrip($tripId) {
    // 8-2. trip_id가 없으면 400 응답
    if ($tripId <= 0) {
      return $this->response->error('INVALID_TRIP_ID', '유효하지 않은 trip_id입니다.', 400);
    }

    // 8-3. 토큰 검증 및 user_id 추출
    $userId = AuthMiddleware::tokenResponse($this->request); // 검증 실패시 error

    // 8-4. TripsService의 deleteTrip 호출
    $deleted = $this->tripsService->deleteTrip($userId, (int)$tripId);

    // 8-5. 삭제 실패 시 404 응답
    if ($deleted === false) {
      return $this->response->error('DELETION_FAILED', '여행 삭제에 실패했습니다.', 404);
    }

    // 8-6. 성공 시 응답 (삭제된 trip_id 반환)
    return $this->response->success(
      ['trip_id' => $tripId],
      200
    );
  }

}