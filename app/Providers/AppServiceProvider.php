<?php

namespace App\Providers;

use App\Models\PublicOrder;
use App\Models\StoreSetting;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Carbon::setLocale('id');

        View::composer('layouts.app', function ($view) {
            $pendingOnlineOrdersCount = 0;
            try {
                $pendingOnlineOrdersCount = PublicOrder::where('status', 'pending')->count();
            } catch (\Throwable $e) {
                $pendingOnlineOrdersCount = 0;
            }
            $view->with('pendingOnlineOrdersCount', $pendingOnlineOrdersCount);
        });

        View::composer(['layouts.public', 'catalog.*', 'pos.receipt', 'fleet.jobs.waybill'], function ($view) {
            try {
                $storeSetting = StoreSetting::current();
            } catch (\Throwable $e) {
                $storeSetting = new StoreSetting([
                    'store_name' => 'Sentosa Bangunan',
                    'store_address' => StoreSetting::DEFAULT_ADDRESS,
                    'show_customer_accounts' => true,
                ]);
            }
            $view->with('storeSetting', $storeSetting);
        });
    }
}
