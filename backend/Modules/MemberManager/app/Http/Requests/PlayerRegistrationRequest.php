<?php

namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlayerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('additional_contacts') && is_string($this->additional_contacts)) {
            $contacts = json_decode($this->additional_contacts, true);
            if (is_array($contacts)) {
                // If it's a single associative array (object), wrap it in an array
                if (\Illuminate\Support\Arr::isAssoc($contacts)) {
                    $contacts = [$contacts];
                }
                $this->merge([
                    'additional_contacts' => $contacts
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            // Mandatory Player (Person) fields
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'mobile_country_code' => 'nullable|string|max:5',
            'mobile' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'age' => 'nullable|integer|min:0|max:100',
            'dob' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'photo' => 'nullable|image|max:2048', // max 2MB
            
            // Additional Contacts array
            'additional_contacts' => 'nullable|array',
            'additional_contacts.*.name' => 'required_with:additional_contacts|string|max:100',
            'additional_contacts.*.country_code' => 'nullable|string|max:5',
            'additional_contacts.*.phone_number' => 'required_with:additional_contacts|string|max:20',
            'additional_contacts.*.relation' => 'nullable|string|max:50',
            
            // Optional Member fields
            'branch_id' => 'nullable|exists:branches,id',
            'member_number' => 'nullable|string|unique:members,member_number',
            
        ];
    }
    
    public function messages(): array
    {
        return [
            'first_name.required' => 'اسم اللاعب حقل إجباري',
            'last_name.required' => 'كنية اللاعب حقل إجباري',
            'mobile.required' => 'رقم اللاعب (هاتف محمول) حقل إجباري',
            'gender.required' => 'الجنس حقل إجباري',
            'age.required_without' => 'عمر اللاعب مطلوب في حال لم يتم إدخال تاريخ الميلاد',
            'age.min' => 'لا يمكن أن يكون العمر قيمة سالبة',
            'dob.required_without' => 'تاريخ الميلاد مطلوب في حال لم يتم إدخال العمر',
            'photo.image' => 'يجب أن يكون الملف المرفق صورة',
            'photo.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميغابايت',
            'additional_contacts.*.name.required_with' => 'اسم جهة الاتصال الإضافية مطلوب',
            'additional_contacts.*.phone_number.required_with' => 'رقم هاتف جهة الاتصال الإضافية مطلوب',
        ];
    }
}
