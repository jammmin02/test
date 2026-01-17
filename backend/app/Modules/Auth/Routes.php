<?php
    use Tripmate\Backend\Core\Request;
    use Tripmate\Backend\Core\Response;
    use Tripmate\Backend\Modules\Auth\Controllers\AuthController;

    // Auth 라우트 등록
    return function (\AltoRouter $router, Request $request, Response $response): void {
        // 회원가입 라우팅 등록
        $router->map('POST', '/api/v1/users', [new AuthController($request, $response), 'userRegister']);

        // 로그인 라우팅 등록
        $router->map('POST', '/api/v1/auth/login', [new AuthController($request, $response), 'userLogin']);

        // 로그아웃 라우팅 등록
        $router->map('POST', '/api/v1/auth/logout', [new AuthController($request, $response), 'userLogout']);
    };