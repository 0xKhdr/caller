<?php

declare(strict_types=1);

namespace Raid\Caller\Providers;

use Illuminate\Support\ServiceProvider;
use Raid\Caller\Traits\InteractsWithProvider;

class CallerServiceProvider extends ServiceProvider
{
    use InteractsWithProvider;

    public function register(): void
    {
        $this->publishConfig();
    }

    public function boot(): void
    {
        //
    }
}
