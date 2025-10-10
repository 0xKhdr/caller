<?php

declare(strict_types=1);

namespace Raid\Caller\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Raid\Caller\Builders\Contracts\Builder;
use Raid\Caller\Builders\Contracts\RequestBuilder as RequestBuilderContract;
use Raid\Caller\Builders\Implementations\RequestBuilder;
use Raid\Caller\Executors\Contracts\Executor;
use Raid\Caller\Executors\Contracts\RequestExecutor as RequestExecutorContract;
use Raid\Caller\Executors\Implementations\RequestExecutor;
use Raid\Caller\Mappers\Contracts\Mapper;
use Raid\Caller\Mappers\Contracts\ResponseMapper as ResponseMapperContract;
use Raid\Caller\Mappers\Implementations\ResponseMapper;
use Raid\Caller\Traits\InteractsWithProvider;

class CallerServiceProvider extends ServiceProvider
{
    use InteractsWithProvider;

    public function register(): void
    {
        $this->registerCoreContracts();
        $this->registerBaseContracts();
        $this->registerAliases();
        $this->mergeConfigFrom(__DIR__.'/../../config/caller.php', 'caller');
        $this->registerCallFacade();
    }

    public function boot(): void
    {
        $this->publishConfig();
    }

    protected function registerCoreContracts(): void
    {
        // Main contract bindings
        $this->app->bind(RequestBuilderContract::class, RequestBuilder::class);
        $this->app->bind(RequestExecutorContract::class, RequestExecutor::class);
        $this->app->bind(ResponseMapperContract::class, ResponseMapper::class);

        // Bind with configuration
        $this->app->when(RequestExecutor::class)
            ->needs('$config')
            ->give(function (Application $app) {
                return array_merge(
                    config('caller.default', []),
                    config('caller.executor', [])
                );
            });
    }

    protected function registerBaseContracts(): void
    {
        // Base interface bindings - useful if someone wants to extend your package
        $this->app->bind(Builder::class, RequestBuilderContract::class);
        $this->app->bind(Executor::class, RequestExecutorContract::class);
        $this->app->bind(Mapper::class, ResponseMapperContract::class);
    }

    protected function registerAliases(): void
    {
        // Service aliases for easy access
        $this->app->alias(RequestBuilderContract::class, 'caller.builder');
        $this->app->alias(RequestExecutorContract::class, 'caller.executor');
        $this->app->alias(ResponseMapperContract::class, 'caller.mapper');

        // Base interface aliases
        $this->app->alias(Builder::class, 'caller.builder.contract');
        $this->app->alias(Executor::class, 'caller.executor.contract');
        $this->app->alias(Mapper::class, 'caller.mapper.contract');
    }

    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../../config/caller.php' => config_path('caller.php'),
        ], 'caller-config');
    }
}
