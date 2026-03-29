<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLocationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role ?? 'system';
        return in_array($role, ['supervisor', 'admin', 'system']);
    }

    public function rules(): array
    {
        return [
            'isBlocked' => 'required|boolean',
            'reason' => 'nullable|string|max:255',
        ];
    }
}
