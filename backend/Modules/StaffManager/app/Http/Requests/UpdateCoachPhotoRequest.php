<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoachPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('photo') === 'null' || $this->input('photo') === '' || $this->input('photo') === 'undefined') {
            $this->merge(['photo' => null]);
        }

        if ($this->has('delete_photo')) {
            $this->merge([
                'delete_photo' => filter_var($this->delete_photo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'photo'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'delete_photo' => ['nullable', 'boolean'],
        ];
    }
}
