<?php
    namespace Tripmate\Backend\Common\Utils;

    use Firebase\JWT\JWT as JJWT;
    use Firebase\JWT\Key;
    use Firebase\JWT\ExpiredException;
    use Firebase\JWT\SignatureInvalidException;
    use Tripmate\Backend\Core\Response;

    // JWT 발급 및 검증
    class Jwt {
        // JWT 발급
        public static function encode($userId) {
            // 시크릿 키 설정
            $secretKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWUsImlhdCI6MTUxNjIzOTAyMn0.KMUFsIDTnFmyG3nMiGM6H9FNFUROf3wh7SmqJp-QV30';

            // 페이로드 정의
            $payload = [
                'iss' => "tripmate.com", // 발급자
                'aud' => "tripmate/client.com", // 대상자
                'iat' => time(), // 발급 시간
                'exp' => time() + 43200, // 12시간 유효
                'jti' => self::jtiCreate(), // 고유 식별
                'userId' => $userId
            ];

            // JWT 인코딩 생성
            $jwt = JJWT::encode($payload, $secretKey, 'HS256');
            return $jwt;
        }

        // JWT 검증
        public static function decode($jwt) {
            $response = new Response();

            // 시크릿 키 설정
            $secretKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWUsImlhdCI6MTUxNjIzOTAyMn0.KMUFsIDTnFmyG3nMiGM6H9FNFUROf3wh7SmqJp-QV30';

            try {
                // 디코딩
                $decode = JJWT::decode($jwt, new Key($secretKey, 'HS256'));
            } catch (SignatureInvalidException $e) {
                // 서명 검증 실패 처리
                $response->error("TOKEN_SIGNATURE_INVALID", "토큰 서명이 유효하지 않습니다.", 403);
                exit;
            } catch (ExpiredException $e) {
                // 토큰 만료 처리
                $response->error("TOKEN_EXPIRED", "토큰이 만료되었습니다. 다시 로그인해주세요.", 401);
                exit;
            }
        
            // 유저 아이디 확인
            $userId = $decode->userId; 

            // id 없을 시
            if (!$userId) {
                $response->error("TOKEN_UNKNOWN_ERROR", "토큰 처리 중 알 수 없는 오류가 발생했습니다.", 500);
                exit;
            }
            
            return $userId;
            }

        // JTI 생성 함수
        private static function jtiCreate() {
            $jti = '';
            // 난수 반복 생성
            for($i = 1 ; $i <= 32 ; $i++) {
                // 난수 생성
                $randomNum = rand(0, 9);
                $jti .= (string)$randomNum;
            }
            // jti 반환
            return $jti;
        }
    }
