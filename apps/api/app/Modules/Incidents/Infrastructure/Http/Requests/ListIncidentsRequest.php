<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListIncidentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role ?? 'system';
        return in_array($role, ['operator', 'supervisor', 'admin', 'system']);
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'locationId' => ['nullable', 'string'],
            'productId' => ['nullable', 'string'],
        ];
    }
}
