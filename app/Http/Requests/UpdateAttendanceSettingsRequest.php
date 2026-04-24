<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'absence_after_time' => ['required', 'date_format:H:i'],
            'tardies_before_absence' => ['required', 'integer', 'min:1', 'max:20'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
        ];
    }
}
