<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('units.update') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Unit $unit */
        $unit = $this->route('unit');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Unit::class, 'name')->ignore($unit)],
            'code' => ['required', 'string', 'max:20', Rule::unique(Unit::class, 'code')->ignore($unit)],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:3'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower((string) $this->string('code')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
