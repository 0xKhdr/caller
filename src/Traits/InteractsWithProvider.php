<?php

namespace Raid\Caller\Traits;

trait InteractsWithProvider
{
    protected function publishConfig(): void
    {
        $configResources = glob(__DIR__.'/../../config/*.php');

        foreach ($configResources as $configPath) {
            $this->publishes([
                $configPath => config_path(basename($configPath)),
            ], 'caller');

            $this->mergeConfigFrom($configPath, 'caller');
        }
    }
}
