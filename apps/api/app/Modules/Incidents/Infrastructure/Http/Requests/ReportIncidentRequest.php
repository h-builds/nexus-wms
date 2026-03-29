<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role ?? 'system';
        return in_array($role, ['operator', 'supervisor', 'admin', 'system']);
    }

    public function rules(): array
    {
        return [
            'productId' => ['required', 'string', 'uuid'],
            'locationId' => ['nullable', 'string', 'uuid'],
            'type' => ['required', 'string', Rule::in([
                'damage', 'shortage', 'overage', 'expiration', 'misplacement', 
                'broken_packaging', 'nonconforming_product', 'picking_blocker', 'lot_error'
            ])],
            'severity' => ['required', 'string', Rule::in(['low', 'medium', 'high'])],
            'description' => ['required', 'string', 'min:1'],
            'quantityAffected' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
