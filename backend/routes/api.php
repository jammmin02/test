<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\UsersController;
use App\Http\Controllers\Trip\TripController;
use App\Http\Controllers\Trip\TripDayController;
use App\Http\Controllers\Trip\ScheduleItemController;
use App\Http\Controllers\Place\PlaceController;
use App\Http\Controllers\Region\RegionController;  

// API Version 2
Route::prefix('v2')->group(function () {
  
  Route::prefix('auth')->group(function () {

    /**
     * 회원 가입
     * GET    /v2/auth/google/register
     * GET   /v2/auth/line/register
     */
    Route::get('{provider}/register', [SocialAuthController::class, 'redirectToRegister'])
      ->whereIn('provider', ['google', 'line'])
      ->name('auth.register.redirect');
    
    /**
     * 회원가입 callback 처리
     * GET    /v2/auth/google/register/callback
     * GET    /v2/auth/line/register/callback
     */
    Route::get('{provider}/register/callback', [SocialAuthController::class, 'handleRegisterCallback'])
      ->whereIn('provider', ['google', 'line'])
      ->name('auth.register.callback');

    /**
     * 로그인
     * GET    /v2/auth/google/login
     * GET    /v2/auth/line/login
     */
    Route::get('{provider}/login', [SocialAuthController::class, 'redirectToLogin'])
      ->whereIn('provider', ['google', 'line'])
      ->name('auth.login.redirect');
    
    /**
     * 로그인 callback 처리
     * GET    /v2/auth/google/login/callback
     * GET    /v2/auth/line/login/callback
     */
    Route::get('{provider}/login/callback', [SocialAuthController::class, 'handleLoginCallback'])
      ->whereIn('provider', ['google', 'line'])
      ->name('auth.login.callback');


    /**
     * 이메일 필수 에러
     * GET    /v2/auth/error/email-required
     */
    Route::get('error/email-required', [SocialAuthController::class, 'emailRequiredError'])
      ->name('auth.error.email-required');

    /**
     * 기존 계정과 소셜 계정 연결 여부 확인
     * GET    /v2/auth/account/link/confirm
     */
    Route::get('account/link/confirm', [SocialAuthController::class, 'showLinkConfirm'])
      ->name('auth.account.link.confirm');

    /**
     * 미가입 계정으로 로그인 시도 했을 경우
     * GET   /v2/auth/register-required
     */
    Route::get('register-required', [SocialAuthController::class, 'registerRequired'])
      ->name('auth.register-required');

    /**
     * 최초 가입 시 닉네임 입력 페이지
     * POST   /v2/auth/register/nickname
     */
    Route::post('register/nickname', [AuthController::class, 'registerNickname'])
      ->name('auth.register.nickname');
  });

  

  // // 인증된 사용자만 접근 가능한 API
  Route::middleware('auth:api')->group(function () {

    /**
     * Users
     * GET      /v2/users/me
     * DELETE   /v2/users/me
     */
    Route::get('/users/me', [UsersController::class, 'getCurrentUser']);
    Route::delete('/users/me', [UsersController::class, 'deleteCurrentUser']);

    /**
     * Auth
     * POST /v2/auth/logout
     */
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    /**
     * Trip
     * GET         /v2/trips
     * POST        /v2/trips
     * GET         /v2/trips/{trip}
     * PUT/PATCH   /v2/trips/{trip}
     * DELETE      /v2/trips/{trip}
     */
    Route::apiResource('trips', TripController::class);

    /**
     * Trip Days
     * GET    /v2/trips/{trip_id}/days                 목록
     * POST   /v2/trips/{trip_id}/days                 생성
     * GET    /v2/trips/{trip_id}/days/{day_no}        단건 조회
     * PATCH  /v2/trips/{trip_id}/days/{day_no}        메모 수정
     * DELETE /v2/trips/{trip_id}/days/{day_no}        삭제
     * POST   /v2/trips/{trip_id}/days/reorder         순서 변경
    */
    Route::get   ('/trips/{trip_id}/days', [TripDayController::class, 'index']);
    Route::post  ('/trips/{trip_id}/days', [TripDayController::class, 'store']);
    Route::get   ('/trips/{trip_id}/days/{day_no}', [TripDayController::class, 'show']);
    Route::patch ('/trips/{trip_id}/days/{day_no}', [TripDayController::class, 'updateMemo']);
    Route::delete('/trips/{trip_id}/days/{day_no}', [TripDayController::class, 'destroy']);
    Route::post  ('/trips/{trip_id}/days/reorder', [TripDayController::class, 'reorder']);

    /**
     * Schedule Items
     * GET    /v2/trips/{trip_id}/days/{day_no}/items                  목록
     * POST   /v2/trips/{trip_id}/days/{day_no}/items                  생성
     * GET    /v2/trips/{trip_id}/days/{day_no}/items/{seq_no}         단건 조회
     * PATCH  /v2/trips/{trip_id}/days/{day_no}/items/{seq_no}         수정(visit_time/memo)
     * DELETE /v2/trips/{trip_id}/days/{day_no}/items/{seq_no}         삭제
     * POST   /v2/trips/{trip_id}/days/{day_no}/items/reorder          순서 변경
     */
    Route::get   ('/trips/{trip_id}/days/{day_no}/items', [ScheduleItemController::class, 'index']);
    Route::post  ('/trips/{trip_id}/days/{day_no}/items', [ScheduleItemController::class, 'store']);
    Route::get   ('/trips/{trip_id}/days/{day_no}/items/{seq_no}', [ScheduleItemController::class, 'show']);
    Route::patch ('/trips/{trip_id}/days/{day_no}/items/{seq_no}', [ScheduleItemController::class, 'update']);
    Route::delete('/trips/{trip_id}/days/{day_no}/items/{seq_no}', [ScheduleItemController::class, 'destroy']);
    Route::post  ('/trips/{trip_id}/days/{day_no}/items/reorder', [ScheduleItemController::class, 'reorder']);

    /**
     * Places
     * GET    /v2/places/external-search
     * GET    /v2/places/{place_id}
     * GET    /v2/places/reverse-geocode
     * GET    /v2/places/place-geocode
     * GET    /v2/places/nearby
     * POST   /v2/places/from-external
     */
    Route::get('/places/external-search', [PlaceController::class, 'externalSearch']);
    Route::get('/places/reverse-geocode', [PlaceController::class, 'reverseGeocode']);
    Route::get('/places/place-geocode', [PlaceController::class, 'placeGeocode']);
    Route::get('/places/nearby', [PlaceController::class, 'nearbyPlaces']);
    Route::post('/places/from-external', [PlaceController::class, 'createPlaceFromExternal']);
    Route::get('/places/{place}', [PlaceController::class, 'getPlaceById']);

    /**
     * Regions
     * GET  /v2/regions
     */
    Route::get('/regions', [RegionController::class, 'listRegions']);
  });
});