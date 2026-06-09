<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListDecisionTracesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string'],
            'severity' => ['nullable', 'string'],
            'agentDomain' => ['nullable', 'string'],
            'sort' => ['nullable', 'string'],
        ];
    }
}
