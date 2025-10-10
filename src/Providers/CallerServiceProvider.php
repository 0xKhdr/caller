<?php

declare(strict_types=1);

namespace Raid\Caller\Providers;

use Illuminate\Support\ServiceProvider;
use Raid\Caller\Facades\Call;
use Raid\Caller\Services\Implementations\CacheCallService;
use Raid\Caller\Traits\InteractsWithProvider;

class CallerServiceProvider extends ServiceProvider
{
    use InteractsWithProvider;

    public function register(): void
    {
        $this->registerConfig();
        $this->registerCallFacade();
    }

    public function boot(): void
    {
        //
    }
}
