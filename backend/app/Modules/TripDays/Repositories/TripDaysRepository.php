<?php
// 1. 네임스페이스 선언
namespace Tripmate\Backend\Modules\TripDays\Repositories;

// 2. use 작성
use Tripmate\Backend\Core\DB;
use PDO;

// 3. TripDaysRepository 클래스 정의
class TripDaysRepository {
  // 1. pdo 프러퍼티 정의
  public PDO $pdo;

  // 2. 생성자에서 DB 생성 및 접속 
  public function __construct() {
    // 2-1. DB 객체 생성
    $db = new DB();
    // 2-2. DB 접속
    $this->pdo = $db->getConnection();
  }

  // 3. 트랜잭션 제어 메서드
  public function beginTransaction() : bool {
    // 3-0. 이미 열려있는지 확인
    if ($this->pdo->inTransaction()) {
      return true;
    }

    // 3-1. 실패 시 false 반환
    return $this->pdo->beginTransaction();
  }

  // 4. 커밋 제어 메서드
  public function commit() : bool {
    // 4-1. 트레젝션이 실행중인지 확인
    if ($this->pdo->inTransaction()){
      // 4-2. 실행중이라면 commit 실행
      return $this->pdo->commit();
    }
    // 4-3. 실행중이 아니라면 false 반환
    return false;
  }

  // 5. 롤백 제어 메서드
  public function rollBack() : bool {
    // 5-1. 트레젝션이 실행중인지 확인
    if ($this->pdo->inTransaction()){
      // 5-2. 실행중이라면 rollBack 실행
      return $this->pdo->rollBack();
    }
    // 5-3. 실행중이 아니라면 false 반환
    return false;
  }

  // tirp_i가 user_id 소유인지 확인 메서드
  public function isTripOwner(int $tripId, int $userId) : bool {
    // 1-1. sql 작성
    $sql = "SELECT 1
            FROM Trip
            WHERE trip_id = :trip_id AND user_id = :user_id
            LIMIT 1";

    // 1-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 1-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }
    // 1-4. 쿼리 실행
    $success = $stmt->execute([
      ':trip_id' => $tripId,
      ':user_id' => $userId
    ]);
    
    // 1-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }

    // 1-6. 첫번째 컬럼 값 가져오기 
    $exists = $stmt->fetchColumn();

    // 1-7. 존재하면 true, 없으면 false 반환
    if ($exists === false) {
      return false;
    }
    return true;
  }

  // 1. trip 존재 여부 및 day_count 조회 메서드
  public function getTripMeta(int $tripId) : array|false {
    // 1-1. sql 작성
    $sql = "SELECT trip_id, start_date, end_date, day_count
            FROM Trip
            WHERE trip_id = :trip_id";

    // 1-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 1-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }
    // 1-4. 쿼리 실행
    $success = $stmt->execute([':trip_id' => $tripId]);
    
    // 1-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }

    // 1-6. 결과 반환
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    // 1-7. trip이 존재하지 않을 시 false 반환
    if ($trip === false) {
      return false;
    }

    // 1-7. day_count 숫자형으로 변환
    $trip['day_count'] = (int)$trip['day_count'];

    // 1-8. 성공 시 trip 배열 반환
    return $trip;
    }

  // 2. tripday의 day_no 존재 확인 메서드
  public function existsDayNo(int $tripId, int $dayNo) : bool {
    // 2-1. sql 작성 (trip_id, day_no에 해당하는 행이 존재하는지 확인)
    $sql = "SELECT 1
            FROM TripDay
            WHERE trip_id = :trip_id AND day_no = :day_no
            LIMIT 1";
    // 2-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 2-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }
    // 2-4. 쿼리 실행
    $success = $stmt->execute([
      ':trip_id' => $tripId,
      ':day_no'  => $dayNo
    ]);

    // 2-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }

    // 2-6. 첫번째 컬럼 값 가져오기 
    $exists = $stmt->fetchColumn();

    // 2-7. 존재하면 true, 없으면 false 반환
    if ($exists === false) {
      return false;
    }
    return true;
  }

  public function getMaxDayNo(int $tripId) : int|false {
    $sql = "SELECT MAX(day_no) AS max_day_no
            FROM TripDay
            WHERE trip_id = :trip_id";

    $stmt = $this->pdo->prepare($sql);
    if ($stmt === false) return false;

    $ok = $stmt->execute([':trip_id' => $tripId]);
    if ($ok === false) return false;

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row === false) return false;

    // TripDay 0건일 때 0을 반환해서 '첫 삽입'이 가능하게 한다
    return $row['max_day_no'] === null ? 0 : (int)$row['max_day_no'];
  }

  // 4. (중간 삽입용) addDay 다음으로 day_no +1씩 밀어내는 메서드
  public function shiftDayNos(int $tripId, int $addDay) : bool {
    // 4-1. sql 작성 (해당 trip_id의 day_no가 addDay 이상인 행들의 day_no를 +1씩 증가)
    $sql = "UPDATE TripDay
            SET day_no = day_no + 1, 
                updated_at = NOW()
            WHERE trip_id = :trip_id 
            AND day_no >= :add_day
            ORDER BY day_no DESC "; // 내림차순 정렬로 밀어내기 충돌 방지

    // 4-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 4-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }
    // 4-4. 쿼리 실행
    $success = $stmt->execute([
      ':trip_id' => $tripId,
      ':add_day' => $addDay
    ]);
    // 4-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }
    // 4-6. 성공 시 true 반환
    return true;
  }

  // 5. tripday 삽입 메서드
  public function insertTripDay(int $tripId, int $dayNo, ?string $memo) : int|false {
    // 5-1. sql 작성
    $sql = "INSERT INTO TripDay (trip_id, day_no, memo, created_at, updated_at)
            VALUES (:trip_id, :day_no, :memo, NOW(), NOW())";
    // 5-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 5-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }
    // 5-4. 쿼리 실행
    $success = $stmt->execute([
      ':trip_id' => $tripId,
      ':day_no'  => $dayNo,
      ':memo'    => $memo,
    ]);
    // 5-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }
    // 5-6. 삽입된 행의 ID 가져오기
    $id = (int)$this->pdo->lastInsertId();
    // 5-7. ID가 0이면 false 반환
    if ($id === 0) {
      return false;
    }
    // 5-8. 성공시 ID 반환
    return $id;

  }

  // 6. trip의 day_count 업데이트 메서드
  public function updateTripDayCount(int $tripId) : bool {
    // TripDay의 실제 개수로 동기화
    $sql = "UPDATE Trip t
            SET t.day_count = (
                  SELECT COUNT(*)
                  FROM TripDay td
                  WHERE td.trip_id = t.trip_id
                ),
                t.updated_at = NOW()
            WHERE t.trip_id = :trip_id";

    $stmt = $this->pdo->prepare($sql);
    if ($stmt === false) {
      return false;
    }

    $ok = $stmt->execute([':trip_id' => $tripId]);
    if ($ok === false) {
      // 에러 원인 로그 (헤더 안 깨지게 서버 로그로만)
      $info = $stmt->errorInfo();
      error_log('[TripDays] updateTripDayCount SQL ERROR: ' . var_export($info, true));
      return false;
    }

    return true;
  }

  // 7. tripday 메인 삽입 메서드
  public function createTripDay(int $tripId, ?int $dayNo = null, ?string $memo = null) : int|false {
    // 7-1. tripId에 해당하는 trip 존재 여부 확인 (getTripMeta 사용)
    $trip = $this->getTripMeta($tripId);
    // 7-2. trip이 존재하지 않으면 false 반환
    if ($trip === false) {
      return false;
    }

    // 7-3. 현재 최대 일차 조회 (getMaxDayNo 사용)
    $maxDayNo = $this->getMaxDayNo($tripId);
    // 7-4. 최대 일차 조회 실패 시 false 반환
    if ($maxDayNo === false) {
      return false;
    }

    // 7-5. 삽입할 dayNo 결정
    // - dayNo가 null 이면 제일 마지막에 삽입 (maxDayNo + 1)
    // - dayNo 값이 있으면 그 위치에 삽입 
    $targetDayNo = $dayNo !== null ? (int)$dayNo : ($maxDayNo + 1);

    // 7-6. dayNo가 1보다 작거나, (maxDayNo + 1)보다 크면 false 반환
    if ($targetDayNo < 1 || $targetDayNo > ($maxDayNo + 1)) {
      return false;
    }

    // 7-7. dayNo가 maxDayNo + 1 보다 작은경우 (중간 삽입)
    if ($targetDayNo <= $maxDayNo) {
      // 7-8. 기존 dayNo 밀어내기 (shiftDayNos 사용)
      $shifted = $this->shiftDayNos($tripId, $targetDayNo);
      // 7-9. 밀어내기 실패 시  false 반환
      if ($shifted === false) {
        return false;
      }
    }

    // 7-10. tripday 삽입 (insertTripDay 사용)
    $newId = $this->insertTripDay($tripId, $targetDayNo, $memo);
    // 7-11. 삽입 실패 시 false 반환
    if ($newId === false) {
      return false;
    }

    // 7-12. 성공 시 삽입된 tripday ID 반환
    return $newId;

  }


  // 8. tripday 단건 조회 메서드
  public function findByTripAndDayNo(int $tripId, int $dayNo) :array|false {
    // 8-1. sql 작성 (trip_id와 day_no에 해당하는 tripday 조회)
    $sql = "SELECT td.trip_day_id, td.trip_id, td.day_no,
                   DATE_ADD(t.start_date, INTERVAL (td.day_no - 1) DAY) AS date,
                   td.memo, td.created_at, td.updated_at
            FROM TripDay td
            INNER JOIN Trip t ON t.trip_id = td.trip_id
            WHERE td.trip_id = :trip_id AND td.day_no = :day_no
            LIMIT 1";
    
    // 8-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 8-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }

    // 8-4. 쿼리 실행
    $success = $stmt->execute([
      ':trip_id' => $tripId,
      ':day_no'  => $dayNo
    ]);

    // 8-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }

    // 8-6. 결과 가져오기
    $tripDay = $stmt->fetch(PDO::FETCH_ASSOC);

    // 8-7. 결과가 없으면 false 
    if ($tripDay === false) {
      return false;
    }
    // 8-8. 성공 시 tripday 배열 반환
    return $tripDay;

  }

  // 9. trip_id + day_no로 trip_day_id 가져오기
  public function getTripDayId(int $tripId, int $dayNo) : ?int {
    // 9-1. sql 작성
    $sql = "
        SELECT trip_day_id
        FROM TripDay
        WHERE trip_id = :trip_id AND day_no = :day_no
        ";

    // 9-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);

    // 9-3. 쿼리 준비 실패 시 null 반환
    if ($stmt === false) {
      return null;
    }

    // 9-4. 쿼리 실행
    $success = $stmt->execute([
      ':trip_id' => $tripId,
      ':day_no'  => $dayNo
    ]);

    // 9-5. 쿼리 실행 실패 시 null 반환
    if ($success === false) {
      return null;
    }

    // 9-6. 결과 가져오기
    $tripDayId = $stmt->fetchColumn();

    // 9-7. 결과가 없으면 null 반환
    if ($tripDayId === false) {
      return null;
    }

    // 9-8. 성공 시 trip_day_id 반환
    return (int)$tripDayId;
  }

  // 11. TripDay 삭제 메서드 (PK로 삭제)
  public function deleteTripDay(int $tripDayId) : bool {
    // 11-1. sql 작성
    $sql = "
        DELETE FROM TripDay
        WHERE trip_day_id = :trip_day_id
        ";

    // 11-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);

    // 11-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }

    // 11-4. 쿼리 실행
    $success = $stmt->execute([
      ':trip_day_id' => $tripDayId
    ]);

    // 11-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }

    // 11-6. 성공 시 true 반환
    return true;
  }

  // 12. day_no 재정렬 메서드 (
  // day_no > :deleteDayNo 인 tripDay들의 day_no를 -1씩 감소
  public function reorderDayNosAfterDeletion(int $tripId, int $deleteDayNo) : bool {
    // 12-1. sql 작성
    $sql = "
        UPDATE TripDay
        SET day_no = day_no - 1,
            updated_at = NOW()
        WHERE trip_id = :trip_id AND day_no > :delete_day_no
        ";

    // 12-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);

    // 12-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }

    // 12-4. 쿼리 실행
    $success = $stmt->execute([
      ':trip_id' => $tripId,
      ':delete_day_no' => $deleteDayNo
    ]);

    // 12-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }

    // 12-6. 성공 시 true 반환
    return true;
  }

  // 14. tripday 삭제 메인 메서드
  public function deleteTripDayById(int $tripId, int $dayNo) : bool {
    // 14-1. tripday id 조회
    $tripDayId = $this->getTripDayId($tripId, $dayNo);
    // 14-2. 조회 실패시 false 반환
    if ($tripDayId === null) {
      return false;
    }

    // 14-3. tripday 삭제 (PK로 삭제)
    $tripDayDeleted = $this->deleteTripDay($tripDayId);
    // 14-4. 삭제 실패 시 false 반환
    if ($tripDayDeleted === false) {
      return false;
    }

    // 14-5. day_no 재정렬
    $reordered = $this->reorderDayNosAfterDeletion($tripId, $dayNo);
    // 14-6. 재정렬 실패 시 false 반환
    if ($reordered === false) {
      return false;
    }

    // 14-7. 성공 시 true 반환
    return true;
  }


    // 일차 목록 조회
    public function listRepository($tripId, $userId) {
        // 트레젝션 실행
        $this->pdo->beginTransaction();

        // 유저 아이디 검증
        $userResult = $this->isTripOwner($tripId, $userId);

        if ($userResult !== true) {
            return "NOT_USER_TRIP";
        }

        // 쿼리 작성
        $tripResult = $this->pdo->prepare("SELECT *
                        FROM TripDay 
                        WHERE trip_id=?
                        ORDER BY day_no;");

        if (!$tripResult->execute([$tripId])) {
            $this->pdo->rollback();
            return "DB_SELECT_FAILD";
        }

        // 값 반환
        $data = $tripResult->fetchAll($this->pdo::FETCH_ASSOC);
        if ($data <= 0) {
            $this->pdo->rollback();
            return "DB_SELECT_FAILD";
        }

        $this->pdo->commit();

        return $data;
    }

    // 일차 메모 수정
    public function noteRepository($tripId, $dayId, $memo, $userId) {
        // 유저 아이디 검증
        $userResult = $this->isTripOwner($tripId, $userId);

        if ($userResult !== true) {
            return "NOT_USER_TRIP";
        }

        // 쿼리 작성
        $tripResult = $this->pdo->prepare("UPDATE TripDay
                                    SET memo = :memo
                                    WHERE trip_id = :trip_id AND day_no = :day_no;");
    
        $tripRes = $tripResult->execute(["memo" => $memo, "trip_id" => $tripId, "day_no" => $dayId]);
        
        if (!$tripRes) {
            return "UPDATE_FAIL";
        }

        // 조회
        $tripQuery = $this->pdo->prepare("SELECT *
                        FROM TripDay 
                        WHERE trip_id = :trip_id AND day_no = :day_no;");

        if (!$tripQuery->execute(["trip_id" => $tripId, "day_no" => $dayId])) {
            return "NOT_FOUND";                 
        }

        $data = $tripQuery->fetch($this->pdo::FETCH_ASSOC);
        if (!$data) {
            return "NOT_FOUND";
        }

        return $data;
    }

    // 일차 재배치
    public function relocationDaysRepository($tripId, $orders, $userId) {
        // 유저 아이디 검증
        $userResult = $this->isTripOwner($tripId, $userId);

        if ($userResult !== true) {
            return "NOT_USER_TRIP";
        }

        // 트레젝션 실행
        $this->pdo->beginTransaction();

        // 일차 재정렬(임시 변경)

        foreach ($orders as $order) {
            $dayNo = $order['day_no'];
            // 큰 수를 임시로 day_no에 업데이트
            $tripResult = $this->pdo->prepare("UPDATE TripDay
                                          SET day_no = day_no + 1000
                                          WHERE trip_id = :trip_id AND day_no = :day_no;");

            // 쿼리 실패시
            if(!$tripResult->execute(['trip_id' => $tripId, 'day_no' => $dayNo])) {
                $this->pdo->rollback();
                return "UPDATE_FAIL";
            }
        }
        
        // 일차 재정렬 쿼리 작성
        foreach ($orders as $order) {
            $dayNo = $order['day_no'];
            $newDayNo = $order['new_day_no'];

            // day_no 업데이트 쿼리 작성
            $tripResult = $this->pdo->prepare("UPDATE TripDay
                                    SET day_no = :new_day_no
                                    WHERE trip_id = :trip_id AND day_no = :day_no;");
            
            // 쿼리 실패시
            if(!$tripResult->execute(['new_day_no' => $newDayNo, 'trip_id' => $tripId, 'day_no' => ($dayNo + 1000)])) {
              $this->pdo->rollback();
              return "UPDATE_FAIL";
            } 

            // trip_id 조회
            $daysSelect = $this->pdo->prepare("SELECT trip_day_id FROM TripDay 
                                        WHERE trip_id = :trip_id AND day_no = :day_no;");

            // 조회 쿼리 실패 시 
            if(!$daysSelect->execute(["trip_id" => $tripId, "day_no" => $newDayNo])) {
                $this->pdo->rollback();
                return "SELECT_FAIL";
            }

            $tripDayId = $daysSelect->fetch($this->pdo::FETCH_ASSOC);
            $tripDayId = $tripDayId['trip_day_id'];
            
            // 조회 실패 시 
            if (!$tripDayId) {
                $this->pdo->rollback();
                return "SELECT_FAIL";
            }
            // 일정 아이템의 날짜 수정
            $offset = $newDayNo - $dayNo;

            // trip_day_id를 기준으로 스케쥴 아이템 날짜 이동 수 만큼 조정
            $scheduleItemResult = $this->pdo->prepare("UPDATE ScheduleItem si
                            JOIN TripDay td ON si.trip_day_id = td.trip_day_id
                            SET si.visit_time = DATE_ADD(si.visit_time, INTERVAL :offset DAY)
                            WHERE td.trip_day_id = :trip_day_id");

            // 조정 쿼리 실패
            if(!$scheduleItemResult->execute(["offset" => $offset, "trip_day_id" => $tripDayId])) {
                $this->pdo->rollback();
                return "UPDATE_FAIL";
            }
        }   

        // 반환
        $tripDaysQuery = $this->pdo->prepare("SELECT trip_day_id, trip_id, day_no, memo
                                        FROM TripDay
                                        WHERE trip_id = :trip_id
                                        ORDER BY day_no ASC");

        if (!$tripDaysQuery->execute(['trip_id' => $tripId])) {
            $this->pdo->rollback();
            return "SELECT_FAIL";
        }

        $tripDaysData = $tripDaysQuery->fetchAll($this->pdo::FETCH_ASSOC);

        // 값 실패 시 
        if (!$tripDaysData) {
            $this->pdo->rollback();
            return "NOT_FOUND";
        }

        $this->pdo->commit();

        return $tripDaysData;
    }
}
