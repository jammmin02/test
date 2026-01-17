<?php
    use Tripmate\Backend\Core\Request;
    use Tripmate\Backend\Core\response;
    use Tripmate\Backend\Modules\Places\Controllers\PlacesController;

    // User 라우트 등록
    return function (\AltoRouter $router, Request $request, Response $response): void {
        //  라우팅 등록
        $router->map('GET', '/api/v1/places/external-search', [new PlacesController($request, $response), 'search']);

        //  라우팅 등록
        $router->map('GET', '/api/v1/places/[i:place_id]', [new PlacesController($request, $response), 'singlePlaceSearch']);

        //  라우팅 등록
        $router->map('POST', '/api/v1/places/from-external', [new PlacesController($request, $response), 'placeUpsert']);
    };