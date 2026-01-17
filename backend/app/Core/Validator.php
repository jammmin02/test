<?PHP
    namespace Tripmate\Backend\Core;  // 네임스페이스 정의 (APP가 찾게 하기 위해)

    // validator, NestedValidatonException 라이브러리 가져와 사용
    use Respect\Validation\Validator as v;
    use Respect\Validation\Exceptions\NestedvalidationException as nve;

    // 클래스 정의
    class Validator {
        // 에러 정의
        private function errorCheck($validation, $date) {
            try{
                $validation->assert($date);
            } catch(nve $e) {
                // 모든 에러 메세지 배열을 가져와
                $allErrormessage = $e->getMessages();

                // 연관배열이기에 reset()로 첫 값을 가져온다. 
                return reset($allErrormessage);
            }

            return true;
        }

        // 로그인 유효성 검증
        public function validateUser(array $date) {
            // email, password 검증
            $validation = v::key('email', v::email()->notEmpty()->length(null, 255), true)
                        -> key('password', v::alnum()->length(8, 128)->notEmpty(), true);

            // 에러 확인
            return $this->errorCheck($validation, $date);
        } 

        // 회원가입 유효성 검증
        public function validateUserRegister(array $date) {
            // email, password 검증
            $result = $this->validateUser($date);
            // 에러 발생
            if ($result !== true) {
                return $result;
            }

            // nickname 유효성 검증
            $validation = v::key('nickname', v::notEmpty()->length(1, 50), true);

            return $this->errorCheck($validation, $date);
        }

        // 여행 생성/ 여행 수정 유효성 검증
        public function validateTrip(array $date) {
            // title, region_id, start/end_date 검증
            $validation = v::key('title', v::length(1, 100)->notEmpty(), true)
                        -> key('region_id', v::intVal()->positive()->notEmpty(), true)
                        -> key('start_date', v::date()->notEmpty(), true)
                        -> key('end_date', v::date()->notEmpty(), true);

            return $this->errorCheck($validation, $date);
        }

        // 일차 속성(메모) 수정 유효성 검증
        public function validateMemo(array $date) {
            // memo 검증
            $validation = v::key('memo', v::length(null, 255)->notEmpty(), true);

            return $this->errorCheck($validation, $date);
        }
        
        // 일차 생성 유효성 검증
        public function validateDays(array $date) {
            // memo 검증
            $result = $this->validateMemo($date);
            if ($result !== true) {
                return $result;
            }

            // day_no 검증
            $validation = v::key('day_no', v::intVal()->positive()->notEmpty(), true);
            
            return $this->errorCheck($validation, $date);
        }

        // 일차 재배치 유효성 검증
        public function validateDayRelocation(array $date) {
            // orders 배열 검증
            $validation = v::key('orders', v::arrayType()->notEmpty()->each(
                v::keySet(
                    v::key('day_no', v::notEmpty()->intVal()->positive(), true),
                    v::key('new_day_no', v::notEmpty()->intVal()->positive(), true)
                )
            ), true
        );

            return $this->errorCheck($validation, $date);
        } 

        // 외부 결과를 내부로 저장 유효성 검증
        public function validatePlaceCategory(array $date) {
            $validation = v::key('place', v::stringType()->notEmpty(), false)
                -> key('name', v::stringType()->notEmpty(), true)
                -> key('category', v::stringVal()->notEmpty(), true)
                -> key('address', v::stringType()->notEmpty(), true)
                -> key('external_ref', v::stringType()->notEmpty(), true)
                -> key('lat', v::floatType()->notEmpty(), true)
                -> key('lng', v::floatType()->notEmpty(), true)
                -> key('url', v::notEmpty(), false);

            return $this->errorCheck($validation, $date);
        }

        // 일정 아이템 수정 유효성 검증
        public function validateEditItem(array $date) {
            $validation = v::key('visit_time', v::dateTime()->notEmpty(), true)
                    -> key('seq_no', v::intVal()->positive()->notEmpty(), true);

            return $this->errorCheck($validation, $date);
        }

        // 일정 아이템 추가 유형성 검증
        public function validateAddItem(array $date) {
            // 시간 및 순서 검증
            $result = $this->validateEditItem($date);
            if ($result !== true) {
                return $result;
            }

            // 일정 아이템 검증
            $validation = v::key('place_id', v::notEmpty()->intVal()->positive(), true);

            return $this->errorCheck($validation, $date);
        }

        // 일정 아이템 순서 재배치 유효성 검증
        public function validateRelocationItem(array $date) {
            $validation = v::key('orders', v::arrayType()->notEmpty()->each(
            v::keySet(
                v::key('item_id', v::intVal()->positive()->notEmpty(), true),
                v::key('new_seq_no', v::intVal()->positive()->notEmpty(), true)
            )
        ), true
    );

            return $this->errorCheck($validation, $date);
        }

        /**  @param  */
        public function validateItemId($date) {
            // 일정 아이템 id 검증
            $validation = v::notEmpty()->intVal()->positive();

            return $this->errorCheck($validation, $date);
        }

        /**  @param  */
        public function validatePlaceId($date) {
            // 장소 id 검증
            $validation = v::notEmpty()->intVal()->positive();

            return $this->errorCheck($validation, $date);
        }
        
        /**  @param  */
        public function validateDayNo($date) {
            // 일차 id 검증
            $validation = v::notEmpty()->intVal()->positive();

            return $this->errorCheck($validation, $date);
        }

        /**  @param  */
        public function validateTripId($date) {
            // 여행 생성 id
            $validation = v::notEmpty()->intVal()->positive();

            return $this->errorCheck($validation, $date);
        }

        // // 지역 유효성 검증
        // /**  @param  */
        // public function validationRegion(array $date) {
        //     $validation = v::key('rid', v::notEmpty()->intVal());

        //     return $this->errorCheck($validation, $date);
        // }


        /**  @param 쿼리*/
        public function validateRegionSearch($date) {
            // 지역 이름 검증
            $validation = v::key('query', v::notEmpty()->stringType(), true);

            return $this->errorCheck($validation, $date);
        }

        /**  @param 쿼리*/
        // 외부 지도 장소 검색 유효성 검증
        public function validatePlace(array $date) {
            // 장소 검증
            $validation = v::key('place', v::notEmpty(), true)
                -> key('radius', v::stringType(), false)
                -> key('lat', v::floatVal()->notEmpty(), false)
                -> key('lng', v::floatVal()->notEmpty(), false)
                -> key('sort', v::stringType(), false)
                -> key('page', v::intVal(), false);

            return $this->errorCheck($validation, $date);
        }

        /**  @param 쿼리*/
        // 일정 아이템 목록 유효성 검증
        public function validateItem(array $date) {
            $validation = v::key('page', v::intVal(), false)
                        -> key('sort', v::stringType(), false);
            
            return $this->errorCheck($validation, $date);
        }
    }

    
