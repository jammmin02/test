<?php
    use Tripmate\Backend\Core\Request;
    use Tripmate\Backend\Core\response;
    use Tripmate\Backend\Modules\Users\Controllers\UsersController;

    // User 라우트 등록
    return function (\AltoRouter $router, Request $request, Response $response): void {
        //  라우팅 등록
        $router->map('GET', '/api/v1/users/me', [new UsersController($request, $response), 'userMyPage']);

        //  라우팅 등록
        $router->map('DELETE', '/api/v1/users/me', [new UsersController($request, $response), 'userSecession']);
    };