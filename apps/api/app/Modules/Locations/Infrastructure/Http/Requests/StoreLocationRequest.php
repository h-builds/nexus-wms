<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouseCode' => ['required', 'string', 'max:255'],
            'zone' => ['required', 'string', 'max:255'],
            'aisle' => ['required', 'string', 'max:255'],
            'rack' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'max:255'],
            'bin' => ['required', 'string', 'max:255'],
        ];
    }
}
