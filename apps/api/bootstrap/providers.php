<?php

use App\Providers\AppServiceProvider;
use App\Modules\Product\Infrastructure\Providers\ProductServiceProvider;

return [
    AppServiceProvider::class,
    ProductServiceProvider::class,
    App\Modules\Locations\Infrastructure\Providers\LocationsServiceProvider::class,
    App\Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider::class,
    App\Modules\Movements\Infrastructure\Providers\MovementServiceProvider::class,
    App\Modules\Incidents\Infrastructure\Providers\IncidentServiceProvider::class,
];
