<?php

namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlayerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'age' => 'required|integer|min:4|max:100',
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
            'age.required' => 'عمر اللاعب حقل إجباري',
            'photo.image' => 'يجب أن يكون الملف المرفق صورة',
            'photo.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميغابايت',
            'additional_contacts.*.name.required_with' => 'اسم جهة الاتصال الإضافية مطلوب',
            'additional_contacts.*.phone_number.required_with' => 'رقم هاتف جهة الاتصال الإضافية مطلوب',
        ];
    }
}
