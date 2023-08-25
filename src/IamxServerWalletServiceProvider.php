<?php

namespace IAMXID\IamxServerWallet;


use IAMXID\IamxServerWallet\Commands\IamxCreateWallet;
use IAMXID\IamxServerWallet\Commands\IamxDeleteWallet;
use Illuminate\Support\ServiceProvider;

class IamxServerWalletServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('ServerWallet', function ($app) {
            return new ServerWallet();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                IamxCreateWallet::class,
                IamxDeleteWallet::class
            ]);
        }
    }
}
