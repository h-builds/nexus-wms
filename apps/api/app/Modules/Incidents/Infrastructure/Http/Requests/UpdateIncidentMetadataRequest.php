<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateIncidentMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Immutable fields are explicitly rejected to prevent silent data corruption.
     */
    private const IMMUTABLE_FIELDS = [
        'type', 'severity', 'description', 'productId',
        'locationId', 'quantityAffected', 'reportedBy', 'createdAt',
    ];

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:2000',
            'assignedTo' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach (self::IMMUTABLE_FIELDS as $field) {
                if ($this->has($field)) {
                    $validator->errors()->add($field, "The field '{$field}' is immutable and cannot be updated.");
                }
            }
        });
    }
}
