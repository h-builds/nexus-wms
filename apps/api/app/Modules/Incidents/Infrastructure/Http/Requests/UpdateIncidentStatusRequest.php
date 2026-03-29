<?php

declare(strict_types=1);

namespace App\Modules\Incidents\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateIncidentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role ?? 'system';
        return in_array($role, ['supervisor', 'admin', 'system']);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([
                'open', 'in_review', 'resolved', 'closed'
            ])],
        ];
    }
}
