<?php

declare(strict_types=1);

namespace App\Modules\Movements\Infrastructure\Http\Requests;

use App\Modules\Movements\Domain\Enums\AdjustmentReason;
use App\Modules\Movements\Domain\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Handled by middleware or policy, true for MVP
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $types = array_map(fn($t) => $t->value, MovementType::cases());
        $adjustmentReasons = array_map(fn($r) => $r->value, AdjustmentReason::cases());

        return [
            'productId' => 'required|string|exists:products,id',
            'type' => ['required', 'string', Rule::in($types)],
            'quantity' => 'required|integer|min:1',
            'fromLocationId' => 'nullable|string|exists:locations,id',
            'toLocationId' => 'nullable|string|exists:locations,id',
            'reference' => 'nullable|string|max:255',
            'lotNumber' => 'nullable|string|max:255',
            'reason' => ['nullable', 'string', Rule::in($adjustmentReasons)],
        ];
    }
}
