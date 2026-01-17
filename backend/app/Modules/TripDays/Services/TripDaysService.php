<?php
// 1. 네임스페이스 선언
namespace Tripmate\Backend\Modules\TripDays\Services;

// 2. TripsRepository 클래스 로드 및 Date 유틸리티 로드
use Tripmate\Backend\Common\Utils\Date;
use Tripmate\Backend\Modules\TripDays\Repositories\TripDaysRepository;

// 임시 클래스 작성
class TripDaysService {
  
  // 3. TripDaysRepository 프러퍼티 정의
  public TripDaysRepository $tripDaysRepository;

  // 4. 생성자에서 TripDaysRepository 초기화
  function __construct() {
    $this-> tripDaysRepository  = new TripDaysRepository(); 
  }

  // 5. 여행 추가 메서드 
  public function addTripDay(int $userId, int $tripId, int $dayNo, ?string $memo = null): int|false {
    // 5-0. tripId가 userId 소유인지 확인
    if (!$this -> tripDaysRepository -> isTripOwner($tripId, $userId)) {
      return false;
    }

    // 5-1. 트레젝션 시작
    if (!$this -> tripDaysRepository -> beginTransaction()) {
      return false;
    }
    // 5-2. 여행 일자 추가
    $tripDayId = $this -> tripDaysRepository -> createTripDay($tripId, $dayNo, $memo,);
    
    // 5-3. 여행 일자 추가 실패 시 롤백 후 false 반환
    if ($tripDayId === false) {
      $this -> tripDaysRepository -> rollBack();
      return false;
    }

    // 5-4. 커밋이 실패하면 롤백 후 false 반환
    if (!$this -> tripDaysRepository -> commit()) {
      $this -> tripDaysRepository -> rollBack();
      return false;
    }
    // 5-5. 성공 시 여행 일자 ID 반환
    return $tripDayId;
    
  }

  // 6. 여행 단건 조회 메서드
  public function getTripDay(int $userId, int $tripId, int $dayNo): array|false {
    
    // 6-0. tripId가 userId 소유인지 확인
    if (!$this -> tripDaysRepository -> isTripOwner($tripId, $userId)) {
      return false;
    }

    // 6-1. 여행 단건 조회
    $tripDay = $this -> tripDaysRepository -> findByTripAndDayNo($tripId, $dayNo);

    // 6-2. 조회 실패 시 false 반환
    if ($tripDay === false) {
      return false;
    }

    // 6-3. 성공 시 여행 정보 배열 반환
    return $tripDay;
  }

  // 7. 여행 일자 삭제 메서드
  public function deleteTripDay(int $userId, int $tripId, int $dayNo): bool {
    // 7-0. tripId가 userId 소유인지 확인
    if (!$this -> tripDaysRepository -> isTripOwner($tripId, $userId)) {
    
      return false;
    }

    // 7-1. 트레젝션 시작
    if (!$this -> tripDaysRepository -> beginTransaction()) {
      return false;
    }

    // 7-2. 여행 일자 삭제
    $deleted = $this -> tripDaysRepository ->  deleteTripDayById($tripId, $dayNo);

    // 7-3. 삭제 실패 시 롤백 후 false 반환
    if ($deleted === false) {
      $this -> tripDaysRepository -> rollBack();
      return false;
    }

    // 7-4. 커밋이 실패하면 롤백 후 false 반환
    if (!$this -> tripDaysRepository -> commit()) {
      $this -> tripDaysRepository -> rollBack();
      return false;
    }

    // 7-5. 성공 시 true 반환
    return true;
  }

    // 8. 일차 목록 조회
    public function daysListService($tripId, $userId) {
        // db 전달
        $result = $this->tripDaysRepository->listRepository($tripId, $userId);

        return $result;
    }

    // 9. 노트 수정
    public function noteService($tripId, $dayId, $memo, $userId) {
        // db 전달
        $result = $this->tripDaysRepository->noteRepository($tripId, $dayId, $memo, $userId);
    
        // 반환
        return $result;
    }

    // 10. 일자 재배치
    public function relocationDaysService($tripId, $orders, $userId) {
        // db 전달
        $result = $this->tripDaysRepository->relocationDaysRepository($tripId, $orders, $userId);
    
        // 반환
        return $result;
    }
}
