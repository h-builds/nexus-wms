<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLocationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role ?? 'system';
        return in_array($role, ['supervisor', 'admin', 'system'], true);
    }

    public function rules(): array
    {
        return [
            'isBlocked' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255', 'required_if:isBlocked,true'],
        ];
    }

    public function toDTO(string $locationId): \App\Modules\Locations\Application\DTOs\UpdateLocationStatusDTO
    {
        return new \App\Modules\Locations\Application\DTOs\UpdateLocationStatusDTO(
            locationId: $locationId,
            isBlocked: $this->boolean('isBlocked'),
            reason: $this->validated('reason'),
            performedBy: $this->user() ? (string) $this->user()->id : 'system_user',
            correlationId: $this->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
        );
    }
}
