<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class ProductModel extends Model
{
    protected $table = 'products';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sku',
        'name',
        'category',
        'unit_of_measure',
        'attributes',
    ];

    protected $casts = [
        'attributes' => 'array',
    ];
}