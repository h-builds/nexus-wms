<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Providers;

use App\Modules\Product\Domain\Repositories\ProductRepository;
use App\Modules\Product\Infrastructure\Persistence\Eloquent\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

final class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
    }

    public function boot(): void
    {
    }
}