<?php

use App\Providers\AppServiceProvider;
use App\Modules\Product\Infrastructure\Providers\ProductServiceProvider;

return [
    AppServiceProvider::class,
    ProductServiceProvider::class,
];
