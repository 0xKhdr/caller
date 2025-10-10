<?php

namespace Raid\Caller\Traits;

use Illuminate\Support\Arr;
use Raid\Caller\Services\Contracts\Call as CallContract;
use Raid\Caller\Services\Implementations\SimpleCallService;
use RuntimeException;

trait InteractsWithProvider
{
    protected function registerConfig(): void
    {
        $configResources = glob(__DIR__.'/../../config/*.php');

        foreach ($configResources as $configPath) {
            $this->publishes([
                $configPath => config_path(basename($configPath)),
            ], 'caller');

            $this->mergeConfigFrom($configPath, 'caller');
        }
    }

    protected function registerCallService(): void
    {
        $this->app->singleton(CallContract::class, function () {
            if (!($key = config('caller.service'))) {
                throw new RuntimeException('Caller service is not defined in configuration.');
            }
            $class = Arr::get(
                (array) config('caller.services', []),
                $key,
                SimpleCallService::class
            );
            return new $class;
        });
    }
}
