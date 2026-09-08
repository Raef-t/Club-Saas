<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('photo') === 'null' || $this->input('photo') === '') {
            $this->merge(['photo' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }
}
