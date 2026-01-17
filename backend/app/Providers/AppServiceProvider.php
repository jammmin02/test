<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Line\Provider as LineProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // SocialiteProviders 패키지를 사용하여 LINE 소셜 로그인 제공자 등록
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('line', LineProvider::class);
        });
    }
}