<?php 
// AltoRouter 라이브러리 로드
require_once __DIR__ . '/../vendor/autoload.php';

// request 클래스 로드
use Tripmate\Backend\Core\Request;
// response 클래스 로드
use Tripmate\Backend\Core\Response;


// 1. 공용 객체 생성 (request, response, AutoRouter)
$request = new Request();
$response = new Response();
$router = new AltoRouter();

// 2. 모듈 라우터 자동 등록 
//  2-1. 각 모듈의 app/Modules/*/Routes.php 는 
//  "function (AltoRouter $router, Request $request, Response $response): void" 를 반환해야 함
foreach (glob(__DIR__ . '/../app/Modules/*/Routes.php') as $routeFile) {
    $register = require $routeFile;
    if (!is_callable($register)) {
        error_log("Routes.php must return a callable: {$routeFile}");
        continue; // 콜러블 아닌 파일은 스킵
    }
    $register($router, $request, $response);
}

// 3. 실행 하기 없으면 error 404 발생
$match = $router->match();

//  3-1. 찾으면 해당 콜백 실행
if ($match && is_callable($match['target'])) {
  // params를 위치 인자로 전달
  call_user_func_array($match['target'], array_values($match['params'] ?? []));
} 
//  3-2. 못찾으면 error 404 응답
else {
  $response->error('NOT_FOUND', 'Route not found', 404);
}
