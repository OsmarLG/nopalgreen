<?php

namespace App\Http\Requests;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Support\Access;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor?->can('users.update')) {
            return false;
        }

        /** @var User $user */
        $user = $this->route('user');
        $user->loadMissing('roles:id,name');

        if (Access::userHasProtectedRole($user) && ! Access::canManageProtectedRecords($actor)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            ...$this->profileRules($user->id),
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique(User::class, 'username')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'string',
                'exists:roles,name',
                Rule::when(
                    ! Access::canManageProtectedRecords($this->user()),
                    Rule::notIn(Access::PROTECTED_ROLES),
                ),
            ],
            'attendance_starts_at' => ['nullable', 'date'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => strtolower((string) $this->string('username')),
            'email' => strtolower((string) $this->string('email')),
            'roles' => array_values(array_filter((array) $this->input('roles', []))),
            'permissions' => array_values(array_filter((array) $this->input('permissions', []))),
        ]);
    }
}
