<?php
    namespace Tripmate\Backend\Common\Utils;

    // 해쉬
    class Password {
        // 비밀번호 해쉬
        public static function passwordHash($pwd) {
            //비밀번호 해쉬
            $hashPwd = password_hash($pwd, PASSWORD_BCRYPT);

            return $hashPwd;
        }

        // 비밀번호 검증
        public static function passwordValdataion($pwd, $pwdHash) {
            // 비밀번호 검증
            if(password_verify($pwd, $pwdHash)) {
                return true;
            } else {
                return false;
            }

        }
    }