<?php

declare(strict_types=1);

namespace App\Modules\Locations\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListLocationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'warehouseCode' => ['sometimes', 'string', 'max:50'],
            'zone' => ['sometimes', 'string', 'max:50'],
            'aisle' => ['sometimes', 'string', 'max:50'],
            'rack' => ['sometimes', 'string', 'max:50'],
            'bin' => ['sometimes', 'string', 'max:50'],
        ];
    }

    public function toCriteria(): \App\Modules\Locations\Application\DTOs\LocationListCriteria
    {
        return new \App\Modules\Locations\Application\DTOs\LocationListCriteria(
            warehouseCode: $this->validated('warehouseCode'),
            zone: $this->validated('zone'),
            aisle: $this->validated('aisle'),
            rack: $this->validated('rack'),
            bin: $this->validated('bin'),
        );
    }

    public function getPage(): int
    {
        return (int) $this->validated('page', 1);
    }

    public function getPerPage(): int
    {
        return (int) $this->validated('per_page', 50);
    }
}
