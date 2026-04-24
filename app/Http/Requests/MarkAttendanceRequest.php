<?php

namespace App\Http\Requests;

use App\Models\AttendanceRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.mark') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mark_type' => ['required', Rule::in([AttendanceRecord::MARK_ENTRY, AttendanceRecord::MARK_EXIT])],
            'code' => ['required', 'string', 'size:6'],
        ];
    }
}
