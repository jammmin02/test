<?php
// namespace App\Core;
namespace Tripmate\Backend\Core;
// 표준 응답 포멧 클레스
class Response
  {
    // 성공 응답 출력 메서드
    // status = 200 
    public function success(array $data =[], int $status = 200): void
    {
      $this->json([
        'success' => true,
        'data'=> $data
      ], $status);
    }
    // 에러 응답 출력 메서드
    // status = 400
    // error => code, message 포함
    public function error(string $code, string $message, int $status = 400) {
      $this->json([
        'success' => false,
        'error' => [
          'code' => $code,
          'message' => $message,
        ],
      ], $status);
    }

    private function json(array $payload, int $status): void
    {
      // HTTP 응답 코드 설정
      http_response_code($status);
      // JSON 응답 출력
      // UTF-8 설정
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
      exit;

    }

  }


