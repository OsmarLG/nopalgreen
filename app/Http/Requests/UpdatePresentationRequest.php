<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Services\PresentationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePresentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('presentations.update') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'owner_type' => ['required', 'string', Rule::in(PresentationService::ownerTypes())],
            'owner_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ownerType = $this->string('owner_type')->toString();
            $ownerId = (int) $this->input('owner_id');

            if ($ownerType === PresentationService::OWNER_TYPE_RAW_MATERIAL && ! RawMaterial::query()->whereKey($ownerId)->exists()) {
                $validator->errors()->add('owner_id', 'La materia prima seleccionada no existe.');
            }

            if ($ownerType === PresentationService::OWNER_TYPE_PRODUCT && ! Product::query()->whereKey($ownerId)->exists()) {
                $validator->errors()->add('owner_id', 'El producto seleccionado no existe.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'owner_type' => $this->route('ownerType'),
            'is_active' => $this->boolean('is_active', true),
            'barcode' => $this->filled('barcode') ? (string) $this->string('barcode') : null,
        ]);
    }
}
