<?php 
// namespace 작성
namespace Tripmate\Backend\Modules\Trips\Services;

// 1. TripsRepository 클래스 로드 및 Date 유틸리티 로드
use Tripmate\Backend\Common\Utils\Date;
use Tripmate\Backend\Modules\Trips\Repositories\TripsRepository;

// 2. TripsService 클래스 정의
class TripsService {
  // 3. 프러퍼티 정의
  public TripsRepository $tripsRepository;

  // 4. 생성자에서 TripsRepository 초기화
  public function __construct() {
    $this->tripsRepository = new TripsRepository();
  }

  // 5. 여행 생성 메서드
  public function createTrip(int $userId, int $regionId, string $title, string $startDate, string $endDate): int|false {
    // 5-1. 날짜 형식 검증
    if (!Date::isValidDateYmd($startDate) || !Date::isValidDateYmd($endDate)) {
      return false;
    }
    // 5-2. 시작일이 종료일보다 이후인지 검증
    if ($startDate > $endDate) {
      return false;
    }

    // 5-3. 트레젝션 시작
    if (!$this->tripsRepository->beginTransaction()) {
      return false;
    }

    // 5-4. 여행 생성
    $tripId = $this->tripsRepository->insertTrip($userId, $regionId, $title, $startDate, $endDate);
    // 5-5. 여행 생성 실패 시 롤백 후 false 반환
    if ($tripId === false) {
      $this->tripsRepository->rollBack();
      return false;
    }

    // 5-6. tripdays 자동 생성
    $dayCount = Date::calcInclusiveDays($startDate, $endDate);
    // 5-7. dayCount가 1 이하이면 롤백 후 false 반환
    if ($dayCount <= 0) {
      $this->tripsRepository->rollBack();
      return false;
    }

    // 5-7. 여행 일자 수 만큼 tripdays 생성
    for ($dayNo = 1; $dayNo <= $dayCount; $dayNo++) {
      // 실패 시 롤백 후 false 반환
      if (!$this->tripsRepository->insertTripDay($tripId, $dayNo)) {
        $this->tripsRepository->rollBack();
        return false;
      }
    }

    // 5-8. 커밋이 실패하면 롤백 후 false 반환
    if (!$this->tripsRepository->commit()) {
      $this->tripsRepository->rollBack();
      return false;
    }

    // 5-9. 여행 생성 성공 시 tripId 반환
    return $tripId;
   
  }

  // 6. trip_id로 여행 단건 조회 메서드
  public function findTripById(int $tripId, int $userId ): array|false {
   // 6-1. tripId가 0 이하이면 false 반환
    if ($tripId <= 0) {
      return false;
    }
   // 6-2. TripsRepository의 findTripById 메서드 호출
   $trip = $this->tripsRepository->findTripById($tripId, $userId);
   // 6-3. 조회 실패 시 false 반환
    if ($trip === null) {
      return false;
    }
   // 6-4. 조회 성공 시 여행 정보 배열 반환
    return $trip;
  }

  // 7. 여행 목록 조회 메서드
  public function findTrips(int $userId, int $page, int $size): array|false {
    // 7-1. userId가 0 이하이거나 페이지나 크기가 1 이하이면 false 반환
    if ($userId <= 0 || $page <= 0 || $size <= 0) {
      return false;
    }
    // 7-2. TripsRepository의 findTrips 메서드 호출
    $trips = $this->tripsRepository->findTripsByUserId($userId, $page, $size);

    // 7-4. 조회 성공 시 여행 목록 배열 반환
    return $trips;

  }

  // 8. 여행 수정 메서드
  public function updateTrip(int $userId, int $tripId, int $regionId, string $title, string $startDate, string $endDate): bool {
    // 8-1. tripId가 0 이하이면 false 반환
    if ($tripId <= 0) {
      return false;
    }

    // 8-2. 날짜 형식 검증 
    if (!Date::isValidDateYmd($startDate) || !Date::isValidDateYmd($endDate)) {
      return false;
    }

    // 8-3. 시작일이 종료일보다 이후인지 검증 
    if ($startDate > $endDate) {
      return false;
    }

    // 8-4. dayCount 계산 (종료일 - 시작일 + 1)
    $dayCount = Date::calcInclusiveDays($startDate, $endDate);

    // 8-5. 트랜잭션 시작
    if (!$this->tripsRepository->beginTransaction()) {
      // 시작 실패 시 false 반환
      return false;
    }

    // 8-6. TripsRepository의 updateTrip 메서드 호출 (기본정보 수정)
    if (!$this->tripsRepository->updateTrip($userId, $tripId, $regionId, $title, $startDate, $endDate)) {
      // // 8-7. 업데이트 실패 시 롤백 후 false 반환
      $this->tripsRepository->rollBack();
      return false;
    }

    // 8-8. 기존 TripDay 전부 삭제 (deleteTripDaysByTripId 호출)
    if (!$this->tripsRepository->deleteTripDaysByTripId( $tripId)) {
      // 8-9. 삭제 실패 시 롤백 후 false 반환
      $this->tripsRepository->rollBack();
      return false;
    }

    // 8-10. dayCount가 1 이상인지 검증 (1 미만이면 롤백 후 false 반환)
    if ($dayCount <= 0) {
      $this->tripsRepository->rollBack();
      return false;
    }

    // 8-11. dayCount 수만큼 TripDay 새로 생성 (insertTripDay 호출, 실패 시 롤백 후 false 반환)
    for ($dayNo = 1; $dayNo <= $dayCount; $dayNo++) {
      // 실패 시 롤백 후 false 반환
      if (!$this->tripsRepository->insertTripDay($tripId, $dayNo)){
        $this->tripsRepository->rollBack();
        return false;
      }
    }

    // 8-12. 커밋이 실패하면 롤백 후 false 반환
    if (!$this->tripsRepository->commit()) {
      $this->tripsRepository->rollBack();
      return false;
    }

    // 8-13. 모든 작업이 성공하면 true 반환
    return true;
  } 

  // 9. 여행 삭제 메서드
  public function deleteTrip(int $userId, int $tripId): bool {
    // 9-1. tripId가 0 이하이면 false 반환
    if ($tripId <= 0) {
      return false;
    }

    // 9-2. 트랜잭션 시작
    if (!$this->tripsRepository->beginTransaction()) {
      return false;
    }

    // 9-3. TripsRepository의 deleteTripDaysByTripId 메서드 호출 (여행 일자 삭제)
    if (!$this->tripsRepository->deleteTripDaysByTripId($tripId)) {
      // 9-4. 삭제 실패 시 롤백 후 false 반환
      $this->tripsRepository->rollBack();
      return false;
    }

    // 9-5. TripsRepository의 deleteTrip 메서드 호출 (여행 기본정보 삭제)
    if (!$this->tripsRepository->deleteTrip($userId, $tripId)) {
      // 9-6. 삭제 실패 시 롤백 후 false 반환
      $this->tripsRepository->rollBack();
      return false;
    }

    // 9-7. 커밋이 실패하면 롤백 후 false 반환
    if (!$this->tripsRepository->commit()) {
      $this->tripsRepository->rollBack();
      return false;
    }

    // 9-8. 모든 작업이 성공하면 true 반환
    return true;
  }
}