<?php
// 1. namespace 작성
namespace Tripmate\Backend\Modules\ScheduleItems\Repositories;

// 2. DB 클래스 로드 및 pdo 사용
use Tripmate\Backend\Core\DB;
use PDO;
use Respect\Validation\Rules\IntVal;

// 3. ScheduleItemsRepository 클래스 정의
class ScheduleItemsRepository {

    // 4. 생성자에서 DB 접속 및 pdo 초기화
    public PDO $pdo;

    public function __construct() {
      // 4-1. DB 객체 생성 
      $db = new DB();
      // 4-2. db 접속
      $this->pdo = $db->getConnection();
    }

    // 5. 트레젝션 제어 메서드
    public function beginTransaction() :  bool {
        // 5-1. 실패 시 false 반환
        return $this->pdo->beginTransaction();
    }

    // 6. 커밋 제어 메서드
    public function commit() : bool {
        // 6-1. 트레젝션이 실행중인지 확인
        if ($this->pdo->inTransaction()) {
            // 6-2. 실행중이라면 commit 실행
            return $this->pdo->commit();
        }
        // 6-3. 실행중이 아니라면 false 반환
        return false;
    }

    // 7. 롤백 제어 메서드
    public function rollBack() : bool {
        // 7-1. 트레젝션이 실행중인지 확인
        if ($this->pdo->inTransaction()) {
            // 7-2. 실행중이라면 rollBack 실행
            return $this->pdo->rollBack();
        }
        // 7-3. 실행중이 아니라면 false 반환
        return false;
    }

    // 1. tripday 존재 확인 + 잠금 (sql_no 중복 방지)
    public function lockTripDay(int $tripDayId) : bool {
      // 1-1 SQL 작성 (부모 trip_days 테이블 잠금)
      $sql = "SELECT trip_day_id 
              FROM TripDay 
              WHERE trip_day_id= :trip_day_id 
              FOR UPDATE";

      // 1-2. 쿼리 준비
      $stmt = $this->pdo->prepare($sql);
      // 1-3. 쿼리 준비 실패시 false 반환
      if ($stmt === false) {
        return false;
      }
      // 1-4. 쿼리 실행
      $ok = $stmt->execute([':trip_day_id' => $tripDayId]);
      // 1-5. 쿼리 실행 실패시 false 반환
      if ($ok === false) {
        return false;
      }
      // 1-6 . 조회된 결과가 없는 경우 false 반환
      if ($stmt->fetchColumn() === false) {
        return false;
      }
      // 1-7. 성공 시 true 반환
      return true;
    }

    // 2. 다음 seq_no 계산 
    public function getNextSeqNo(int $tripDayId) : int|false {
      // 2-1 SQL 작성
      $sql = "SELECT COALESCE(MAX(seq_no), 0) + 1 AS next_seq_no 
              FROM ScheduleItem 
              WHERE trip_day_id = :trip_day_id";

      // 2-2. 쿼리 준비
      $stmt = $this->pdo->prepare($sql);
      // 2-3. 쿼리 준비 실패시false 반환
      if ($stmt === false) {
        return false  ;
      }
      // 2-4. 쿼리 실행
      $ok = $stmt->execute([':trip_day_id' => $tripDayId]);
      // 2-5. 쿼리 실행 실패시 false 반환
      if ($ok === false) {
        return false;
      }
      // 2-6. 결과 조회 실패시 false 반환
      $next = $stmt->fetchColumn();
      if ($next  === false) {
        return false;
      }
      // 2-7. 성공 시 next_seq_no 반환
      return (int)$next;
    }

    // 3. schedule_item 생성 메서드
    public function insertScheduleItem(
        int $tripDayId,
        ?int $placeId,
        int $seqNo,
        ?string $visitTime,
        ?string $memo
    ) : int|false {
      // 3-1. 빈 문자열은 null로 변환
      if ($visitTime === '') {
        $visitTime = null;
      }
      if ($memo === '') {
        $memo = null;
      }

      // 3-2. SQL 작성
      $sql = " 
        INSERT INTO ScheduleItem
          (trip_day_id, place_id, seq_no, visit_time, memo, created_at, updated_at) 
        VALUES 
          (:trip_day_id, :place_id, :seq_no, :visit_time, :memo, NOW(), NOW())";

      // 3-3. 쿼리 준비
      $stmt = $this->pdo->prepare($sql);
      // 3-4. 쿼리 준비 실패시 false 반환
      if ($stmt === false) {
        return false;
      }

      // 3-5. 쿼리 실행
      $ok = $stmt->execute([
        ':trip_day_id' => $tripDayId,
        ':place_id' => $placeId,
        ':seq_no' => $seqNo,  
        ':visit_time' => $visitTime,
        ':memo' => $memo,
      ]);

      // 3-6. 쿼리 실행 실패시 false 반환
      if ($ok === false) {
        return false;
      }

      // 3-7. 마지막으로 삽입된 ID 반환
      $id = (int)$this->pdo->lastInsertId();
      // 3-8. ID가 없는 경우 false 반환
      if ($id === false) {
        return false;
      }
      return $id;
    }

    // 4. schedule_item 추가 메인 메서드
    public function createScheduleItem (
        int $tripDayId,
        ?int $placeId,
        ?string $visitTime,
        ?string $memo
    ) : int|false {
      // 4-1. trip_day 존재 확인 + 잠금
      $exists = $this->lockTripDay($tripDayId);
      if ($exists === false) {
        return false;
      }

      // 4-2. 다음 seq_no 계산
      $nextSeqNo = $this->getNextSeqNo($tripDayId);
      if ($nextSeqNo === false) {
        return false;
      }

      // 4-3. schedule_item 생성
      $scheduleItemId = $this->insertScheduleItem(
        $tripDayId,
        $placeId,
        $nextSeqNo,
        $visitTime,
        $memo
      );
      if ($scheduleItemId === false) {
        return false;
      }

      // 4-4. 성공 시 schedule_item ID 반환
      return $scheduleItemId;
    }

    // 5. 일정 아이템 목록 조회 메서드
    public function getScheduleItemsByTripDayId(int $tripDayId) : array|false {
      // 5-1. SQL 작성
      $sql = "SELECT 
                item.schedule_item_id,
                item.place_id,
                item.seq_no,
                item.visit_time,
                item.memo,
                place.name AS place_name,
                place.address AS place_address,
                place.lat AS place_lat,
                place.lng AS place_lng
              FROM 
                ScheduleItem AS item
              LEFT JOIN 
                Place AS place ON item.place_id = place.place_id
              WHERE 
                item.trip_day_id = :trip_day_id
              ORDER BY 
                item.seq_no ASC";

      // 5-2. 쿼리 준비
      $stmt = $this->pdo->prepare($sql);
      // 5-3. 쿼리 준비 실패시 false 반환
      if ($stmt === false) {
        return false;
      }

      // 5-4. 쿼리 실행
      $ok = $stmt->execute([':trip_day_id' => $tripDayId]);
      // 5-5. 쿼리 실행 실패시 false 반환
      if ($ok === false) {
        return false;
      }

      // 5-6. 결과 모두 조회
      $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
      // 5-7. 성공 시 결과 반환
      return $items;
    }


    // 6. 일정 아이템 부분 수정메서드 (visittime, memo)
    public function updateScheduleItem(
        int $scheduleItemId,
        ?string $visitTime,
        ?string $memo
    ) : array|false {
      // 6-1. 빈 문자열은 null로 변환
      if ($visitTime === '') {
        $visitTime = null;
      }
      if ($memo === '') {
        $memo = null;
      }

      // 6-2. SQL 작성
      $sql = "UPDATE 
                ScheduleItem 
              SET 
                visit_time = :visit_time,
                memo = :memo,
                updated_at = NOW()
              WHERE 
                schedule_item_id = :schedule_item_id";

      // 6-3. 쿼리 준비
      $stmt = $this->pdo->prepare($sql);
      // 6-4. 쿼리 준비 실패시 false 반환
      if ($stmt === false) {
        return false;
      }

      // 6-5. 쿼리 실행
      $items = $stmt->execute([
        ':visit_time' => $visitTime,
        ':memo' => $memo,
        ':schedule_item_id' => $scheduleItemId,
      ]);

      // 6-6. 쿼리 실행 실패시 false 반환
      if ($items === false) {
        return false;
      }

      // 6-7. 성공 시 수정 된 일정 재조회 후 반환
      $selectSql = "SELECT 
                      schedule_item_id,
                      trip_day_id,
                      place_id,
                      seq_no,
                      visit_time,
                      memo,
                      updated_at
                    FROM 
                      ScheduleItem
                    WHERE 
                      schedule_item_id = :schedule_item_id";
    
      // 6-8. 쿼리 준비
      $selectStmt = $this->pdo->prepare($selectSql);
      // 6-9. 쿼리 준비 실패시 false 반환
      if ($selectStmt === false) {
        return false;
      }
      // 6-10. 쿼리 실행
      $ok = $selectStmt->execute([':schedule_item_id' => $scheduleItemId]);

      // 6-11. 쿼리 실행 실패시 false 반환
      if ($ok === false) {
        return false;
    }
      // 6-12. 결과 조회 실패시 false 반환
      $updatedItem = $selectStmt->fetch(PDO::FETCH_ASSOC);
      if ($updatedItem === false) {
        return false;
      }
      // 6-13. 성공 시 수정된 일정 아이템 반환
      return $updatedItem;
    }

  // 7. 일정 아이템 삭제 메서드 
  public function deleteScheduleItem(int $scheduleItemId) : bool {
    // 7-1. sql 작성
    $sql = "
        DELETE FROM ScheduleItem
        WHERE schedule_item_id = :schedule_item_id
        ";

    // 7-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);

    // 7-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }

    // 7-4. 쿼리 실행
    $success = $stmt->execute([
      ':schedule_item_id' => $scheduleItemId
    ]);

    // 7-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }

    // 7-6. 성공 시 true 반환
    return true;
  }

  // 8. seq_no 재정렬 메서드 (
  // seq_no > :deleteSeqNo 인 schedule_item들의 seq_no를 -1씩 감소
  public function reorderSeqNosAfterDeletion(int $tripDayId, int $deleteSeqNo) : bool {
    // 8-1. sql 작성
    $sql = "
        UPDATE ScheduleItem
        SET seq_no = seq_no - 1
        WHERE trip_day_id = :trip_day_id AND seq_no > :delete_seq_no
        ";

    // 8-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);

    // 8-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      return false;
    }

    // 8-4. 쿼리 실행
    $success = $stmt->execute([
      ':trip_day_id' => $tripDayId,
      ':delete_seq_no' => $deleteSeqNo
    ]);

    // 8-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
    }

    // 8-6. 성공 시 true 반환
    return true;
  }

  // 9. 일정 아이템 삭제 메인 메서드
  public function deleteScheduleDayById(int $tripId, int $dayNo, int $scheduleItemId) : bool {
    // 9-1. 삭제 대상 조회 (trip_day_id / seq_no)
    $findSql = "
      SELECT si.trip_day_id, si.seq_no
      FROM ScheduleItem si
      JOIN TripDay td ON td.trip_day_id = si.trip_day_id
      JOIN Trip t     ON t.trip_id = td.trip_id
      WHERE t.trip_id = :trip_id
        AND td.day_no = :day_no
        AND si.schedule_item_id = :schedule_item_id
      ";
    
    // 9-2. 쿼리 준비
    $findStmt = $this->pdo->prepare($findSql);
    // 9-3. 쿼리 준비 실패 시 false 반환
    if ($findStmt === false) {
      return false;
    }

    // 9-4. 쿼리 실행
    $success = $findStmt->execute([
      ':trip_id' => $tripId,
      ':day_no' => $dayNo,
      ':schedule_item_id' => $scheduleItemId
    ]);
    // 9-5. 쿼리 실행 실패 시 false 반환
    if ($success === false) {
      return false;
      }

    // 9-6. 조회 결과 가져오기
    $dayIdSeqNo = $findStmt->fetch(PDO::FETCH_ASSOC);
    // 9-7. 조회 실패시 false 반환
    if ($dayIdSeqNo === false) {
      return false;
    }

    // 9-8. trip_day_id 추출
    $tripDayId = (int)$dayIdSeqNo['trip_day_id'];
    // 9-9. seq_no 추출
    $deleteSeqNo = (int)$dayIdSeqNo['seq_no'];

    // 9-8. 일정 아이템 삭제 
    $deleted = $this->deleteScheduleItem($scheduleItemId);
    // 9-9. 일정 아이템 삭제 실패 시 false 반환
    if (!$deleted) {
      return false;
    }

    // 9-10. seq_no 재정렬
    $reordered = $this->reorderSeqNosAfterDeletion($tripDayId , $deleteSeqNo);
    // 9-11. 재정렬 실패 시 false 반환
    if (!$reordered) {
      return false;
    }

    // 9-12. 성공 시 true 반환
    return true;
  }

  // 10. 같은 tripday의 scheduleitem 잠금 메서드
  public function lockScheduleItems(int $tripDayId) : bool {
    // 10-1 SQL 작성 (부모 trip_days 테이블 잠금)
    $sql = "SELECT schedule_item_id 
            FROM ScheduleItem 
            WHERE trip_day_id= :trip_day_id 
            FOR UPDATE";

    // 10-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 10-3. 쿼리 준비 실패시 false 반환
    if ($stmt === false) {
      return false;
    }
    // 10-4. 쿼리 실행
    $ok = $stmt->execute([':trip_day_id' => $tripDayId]);
    // 10-5. 쿼리 실행 실패시 false 반환
    if ($ok === false) {
      return false;
    }
    // 10-6 . 성공 시 true 반환
    return true;
  }

  // 11. 특정 item이 속한 trip_day_id 조회 메서드
  public function getTripDayIdByItemId(int $scheduleItemId) : int|false {
    // 11-1 SQL 작성
    $sql = "SELECT trip_day_id 
            FROM ScheduleItem 
            WHERE schedule_item_id = :schedule_item_id";

    // 11-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 11-3. 쿼리 준비 실패시 false 반환
    if ($stmt === false) {
      error_log("getTripDayIdByItemId: prepare 실패");
      return false;
    }
    // 11-4. 쿼리 실행
    $ok = $stmt->execute([':schedule_item_id' => $scheduleItemId]);
    // 11-5. 쿼리 실행 실패시 false 반환
    if ($ok === false) {
      error_log("getTripDayIdByItemId: execute 실패");
      return false;
    }
    // 11-6. 결과 조회 실패시 false 반환
    $tripDayId = $stmt->fetchColumn();
    if ($tripDayId === false) {
      error_log("getTripDayIdByItemId: fetch 실패");
      return false;
    }
    // 11-7. 성공 시 trip_day_id 반환
    return (int)$tripDayId;
  }
  
  // 12. 특정 item의 trip_id, seq_no 잠금 조회 메서드
  public function lockTripIdAndSeqNoByItemId(int $scheduleItemId) : array|false {
    // 12-1 SQL 작성
    $sql = "SELECT trip_day_id, seq_no 
            FROM ScheduleItem 
            WHERE schedule_item_id = :schedule_item_id
            FOR UPDATE";
  
    // 12-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 12-3. 쿼리 준비 실패시 false 반환
    if ($stmt === false) {
      return false;
    }

    // 12-4. 쿼리 실행
    $ok = $stmt->execute([':schedule_item_id' => $scheduleItemId]);
    // 12-5. 쿼리 실행 실패시 false 반환
    if ($ok === false) {
      return false;
    }

    // 12-6. 결과 조회 실패시 false 반환
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result === false) {
      return false;
    }

    // 12-7. 성공 시 trip_day_id, seq_no 반환
    return [
      'trip_day_id' => (int)$result['trip_day_id'],
      'seq_no' => (int)$result['seq_no']
    ];
  }

  // 13. 단일 item 이동을 위한 이동 메서드
  public function shiftScheduleItemSeqNo(
    int $tripDayId, // trip_day_id
    int $fromSeqNo, // 이동 전 seq_no
    int $toSeqNo, // 이동 후 seq_no
    int $moveItemId // 이동 대상 schedule_item_id
    ) : bool {
    // 13-1. 이동 방향에 따른 SQL 작성
    if ($fromSeqNo < $toSeqNo) {
      // 13-2. 아래로 이동 시
      $sql = "
        UPDATE ScheduleItem
        SET seq_no = seq_no - 1
        WHERE trip_day_id = :trip_day_id
          AND seq_no > :from_seq_no
          AND seq_no <= :new_seq_no
          AND schedule_item_id <> :move_item_id
      ";
      $params = [
        ':trip_day_id' => $tripDayId,
        ':from_seq_no' => $fromSeqNo,
        ':new_seq_no' => $toSeqNo,
        ':move_item_id' => $moveItemId
      ];
    } else {
      // 13-3. 위로 이동 시
      $sql = "
        UPDATE ScheduleItem
        SET seq_no = seq_no + 1
        WHERE trip_day_id = :trip_day_id
          AND seq_no >= :new_seq_no
          AND seq_no < :from_seq_no
          AND schedule_item_id <> :move_item_id
      ";
      $params = [
        ':trip_day_id' => $tripDayId,
        ':from_seq_no' => $fromSeqNo,
        ':new_seq_no' => $toSeqNo,
        ':move_item_id' => $moveItemId
      ];
    }
    error_log("shiftScheduleItemSeqNo: SQL - " . $sql);

    // 13-4. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 13-5. 쿼리 준비 실패시 false 반환
    if ($stmt === false) {
      error_log("shiftScheduleItemSeqNo: prepare 실패");
      return false;
    } 

    // 13-6. 쿼리 실행
    $success = $stmt->execute($params);
    // 13-7. 쿼리 실행 실패시 false 반환
    if ($success === false) {
      error_log("shiftScheduleItemSeqNo: execute 실패");
      return false;
    }

    // 13-8. 성공 시 true 반환
    return true;
  }

  // 14. 단일 일정아이템 seq_no 업데이트 메서드
  public function updateItemSeqNo(int $scheduleItemId, int $newSeqNo) : bool {
    // 14-1. SQL 작성
    $sql = "UPDATE ScheduleItem
            SET seq_no = :seq_no, updated_at = NOW()
            WHERE schedule_item_id = :schedule_item_id";
    // 14-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 14-3. 쿼리 준비 실패 시 false 반환
    if ($stmt === false) {
      error_log("updateItemSeqNo: prepare 실패");
      return false;
    }
    // 14-4. 쿼리 실행
    $ok = $stmt->execute([
      ':seq_no' => $newSeqNo,
      ':schedule_item_id' => $scheduleItemId,
    ]);
    // 14-5. 쿼리 실행 실패 시 false 반환
    if ($ok === false) {
      error_log("updateItemSeqNo: execute 실패");
      return false;
    }
    // 14-6. 성공 시 true 반환
    return true;
  }

  // 15. 단일 일정아이템 재배치 메인 메서드
  public function reorderSingleScheduleItem(int $scheduleItemId, int $newSeqNo) : array|bool {
    // 15-1. 일정아이템의 trip_day_id, 현재 seq_no 잠금 조회
    $itemInfo = $this->lockTripIdAndSeqNoByItemId($scheduleItemId);
    // 15-2. 조회 실패 시 false 반환
    if ($itemInfo === false) {
      error_log("일정아이템의 trip_day_id, 현재 seq_no 잠금 조회 실패");
      return false;
    }
    // 15-3. trip_day_id, 현재 seq_no 추출
    $tripDayId = $itemInfo['trip_day_id'];
    $oldSeqNo = $itemInfo['seq_no'];

    // 15-4. 같은 tripday의 scheduleitem 잠금
    $locked = $this->lockScheduleItems($tripDayId);
    // 15-5. 잠금 실패 시 false 반환
    if ($locked === false) {
      error_log("같은 trip_day의 scheduleitem 잠금 실패");
      return false;
    }

    // 15-6. new_seq_no가 old_seq_no와 같으면 그대로 목록 반환
    if ($newSeqNo === $oldSeqNo) {
      $items = $this->getScheduleItemsByTripDayId($tripDayId);
      // 15-7. 업데이트 실패 시 false 반환
      if ($items === false) {
        error_log("일정아이템 목록 조회 실패");
        return false;
      }
      return $items;
    }

    // 15-8. 이동 대상이 되는 아이템 임시로 1000으로 업데이트
    $tempUpdated = $this->updateItemSeqNo($scheduleItemId, 1000);
    // 15-9. 업데이트 실패 시 false 반환 
    if ($tempUpdated === false) {
      error_log("이동 대상 아이템 임시 seq_no 업데이트 실패");
      return false;
    }

    // 15-7. 일정아이템 seq_no 이동
    $shifted = $this->shiftScheduleItemSeqNo($tripDayId, $oldSeqNo, $newSeqNo, $scheduleItemId);
    // 15-8. 이동 실패 시 false 반환
    if ($shifted === false) {
      error_log("일정아이템 seq_no 이동 실패");
      return false;
    }

    
    //15-9. 대상 일정아이템의 seq_no 업데이트
    $ok = $this->updateItemSeqNo( $scheduleItemId, $newSeqNo);
    //15-10. 업데이트 실패 시 false 반환
    if ($ok === false) {
      error_log("대상 일정아이템의 seq_no 업데이트 실패");
      return false;
    }

    // 15-11. 대상 일정아이템의 seq_no 조회
    $items = $this->getScheduleItemsByTripDayId($tripDayId);
    // 14-12. 업데이트 실패 시 false 반환
    if ($items === false) {
      error_log("일정아이템 목록 조회 실패");
      return false;
    }

    // 15-13. 성공하면 수정 된 일정 아이템 목록 반환
    return $items;

  }

  // 16. max seq_no 조회 메서드
  public function getMaxSeqNo(int $tripDayId) : int|false {
    // 16-1 SQL 작성
    $sql = "SELECT MAX(seq_no) AS max_seq_no 
            FROM ScheduleItem 
            WHERE trip_day_id = :trip_day_id";

    // 16-2. 쿼리 준비
    $stmt = $this->pdo->prepare($sql);
    // 16-3. 쿼리 준비 실패시 false 반환
    if ($stmt === false) {
      return false;
    }
    // 16-4. 쿼리 실행
    $ok = $stmt->execute([':trip_day_id' => $tripDayId]);
    // 16-5. 쿼리 실행 실패시 false 반환
    if ($ok === false) {
      return false;
    }
    // 16-6. 결과 조회 실패시 false 반환
    $maxSeqNo = $stmt->fetchColumn();
    if ($maxSeqNo === false) {
      return false;
    }
    // 16-7. 성공 시 max_seq_no 반환
    return (int)$maxSeqNo;
  }

  
}

