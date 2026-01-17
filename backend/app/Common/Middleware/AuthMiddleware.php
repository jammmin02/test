<?php
    namespace Tripmate\Backend\Common\Middleware;

    use Tripmate\Backend\Common\Utils\Jwt;
    use Tripmate\Backend\Core\Request;
    use Tripmate\Backend\Core\Response;

    // Bearer 토큰 검증 → req->user 주입
    class AuthMiddleware {
        // 발급 요청
        public static function tokenRequest($user_id) {
            // 발급 함수 호출
            $jwt = Jwt::encode($user_id);

            if (!$jwt) {
                $response = new Response();
                $response->error("TOKEN_ISSUE_FAILED", "토큰 발급 중 오류가 발생했습니다.", 500);
                exit;
            } else {
                return $jwt;
            }
        }

        // 검증 요청
        public static function tokenResponse(Request $req) {
            $response = new Response();

            // 헤더가 없으면 null로 설정
            $header_token = $req->headers['authorization'] ?? null;
            if ($header_token === null) {
                $response->error("TOKEN_MISSING", "토큰이 제공되지 않았습니다.", 401);
                exit;
            }

            // Bearer 제거
            if (strpos($header_token, 'Bearer ') === 0) {
                $jwt = substr($header_token, 7);
            } else {
                $response->error("TOKEN_FORMAT_INVALID", "토큰 형식이 올바르지 않습니다.", 400);
                exit;
            }

            // 토큰 검증 
            $user_id = Jwt::decode($jwt);

            return $user_id;
        }
    }
