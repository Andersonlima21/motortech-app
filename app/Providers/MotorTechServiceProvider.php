<?php

namespace App\Providers;

use App\Services\ClienteService;
use App\Services\Contracts\ClienteServiceInterface;
use App\Services\Contracts\InsumoServiceInterface;
use App\Services\Contracts\OsServiceInteface;
use App\Services\Contracts\ServicoServiceInterface;
use App\Services\Contracts\VeiculoServiceInterface;
use App\Services\InsumoService;
use App\Services\OsService;
use App\Services\ServicoService;
use App\Services\VeiculoService;
use Illuminate\Support\ServiceProvider;

class MotorTechServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
        $this->app->bind(ClienteServiceInterface::class, ClienteService::class);
        $this->app->bind(InsumoServiceInterface::class, InsumoService::class);
        $this->app->bind(ServicoServiceInterface::class, ServicoService::class);
        $this->app->bind(VeiculoServiceInterface::class, VeiculoService::class);
        $this->app->bind(OsServiceInteface::class, OsService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
