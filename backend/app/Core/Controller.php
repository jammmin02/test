<?php
// namespace App\Core;
namespace Tripmate\Backend\Core;

use Respect\Validation\Rules\Regex;

// 공통 컨트롤러 클래스
class Controller { 
  // 1. request 속성 추가
  public Request $request;
  // 2. response 속성 추가 
  public Response $response;
  
  // 3. 생성자 (request, response 초기화) 
  public function __construct(Request $request, Response $response) {
    $this->request = $request;
    $this->response = $response;
  }

  // 4. 성공 응답 메서드 
  public function success(array $data = [], int $status = 200): void
  {
    $this->response->success($data, $status);
  }  

  // 5. 실패 응답 메서드 
  public function error(string $code, string  $message, int $status = 400): void
  {
    $this->response->error($code, $message, $status);
  }

}
