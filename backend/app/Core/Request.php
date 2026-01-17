<?php
// namespace App\Core;
namespace Tripmate\Backend\Core;

// 1. 요청 데이터 처리 클래스
class Request {
  // 1-1. GET, POST, PUT, DELETE 메서드 처리
  public string $method;
  // 1-2. path 처리
  public string $path;
  // 1-3. query 처리
  public array $query;
  // 1-4. 헤더 처리
  public array $headers;
  // 1-5. JSON 바디 처리
  public array $body;

  // 2. 생성자: 호출시 요청 메서드, path, query, 헤더, 바디 처리
  public function __construct() {
    // 2-1 : $this->method : null인 경우 GET으로 설정
    $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    // 2-2 : uri 저장 
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    // 2-3 : $this->path : uri에서 쿼리 제거한 path 저장
    $this->path = strtok($uri, '?') ?: '/';
    // 2-4 : $this->query : uri에서 쿼리만 배열로 저장 (없으면 빈 배열)
    $this->query = $_GET ?? [];

    // 3. 모든 HTTP 요청 헤더 가져오기
    $rawHeaders = [];
    foreach ($_SERVER as $k => $v) {
      if (strpos($k, 'HTTP_') === 0) {
        $name = strtolower(str_replace('_', '-', substr($k, 5)));
        $rawHeaders[$name] = $v;
      } elseif (in_array($k, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_ENCODING'], true)) {
        $name = strtolower(str_replace('_', '-', $k));
        $rawHeaders[$name] = $v;
      }
    }
    $this->headers = $rawHeaders;
    
    // 4. 소문자로 변환하기
    $lower = [];
    // 4-1 : 3번에서 가져온 헤더를 소문자로 변환 ()
    foreach ($rawHeaders as $key => $value) {
     $lower[strtolower($key)] = $value;
    }
    // 4-2 : headers 속성에 저장
    $this->headers = $lower;

    // 5. JSON 바디 파싱
    $this->body = [];
    // 5-1 : Content-Type 헤더의 타입을 확인
    $contentType = $this->headers['content-type'] ??'';
    // 5-2 : input 스트림에서 바디 읽기
    $raw = file_get_contents('php://input');

    // 5-3 : content-type이 application/json인 경우
    if (stripos($contentType, 'application/json') !== false){
      // 5-4 : JSON 디코딩
      $decoded = json_decode($raw, true);
      // 5-5 : JSON 디코딩 실패시 빈 배열로 설정
      $this->body = is_array($decoded) ? $decoded : [];
    }
    // 5-6 : content-type이 application/json이 아닌 경우
    elseif (stripos ($contentType, 'application/x-www-form-urlencoded') !== false){
      // 5-7 : post 값이 있으면 body에 저장 없으면 빈 배열
      $this->body = $_POST ?? [];
      
    }
  }
  
  }


