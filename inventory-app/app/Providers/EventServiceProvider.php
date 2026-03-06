<?php

namespace App\Providers;

use App\Events\OrderCanceled;
use App\Events\OrderShipped;
use App\Listeners\DeductInventoryOnShip;
use App\Listeners\RestoreInventoryOnCancel;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderShipped::class => [
            DeductInventoryOnShip::class,
        ],
        OrderCanceled::class => [
            RestoreInventoryOnCancel::class,
        ],
    ];
}
