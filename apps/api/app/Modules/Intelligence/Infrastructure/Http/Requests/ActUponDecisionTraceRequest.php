<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ActUponDecisionTraceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actor_id' => ['required', 'string'],
        ];
    }

    public function actorId(): string
    {
        return (string) $this->validated('actor_id');
    }
}
