<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DismissDecisionTraceRequest extends FormRequest
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
            'actor_id' => ['required', 'string'],
        ];
    }
}
