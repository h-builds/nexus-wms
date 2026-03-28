<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unitOfMeasure' => ['required', 'string', 'in:unit,box,pallet,kilogram,liter'],
            'attributes' => ['sometimes', 'array'],
            'actorId' => ['sometimes', 'string', 'max:100'],
        ];
    }
}