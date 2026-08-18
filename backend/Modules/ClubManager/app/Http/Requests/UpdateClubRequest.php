<?php
namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'name'      => 'nullable|string|max:255',
            'logo'      => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'logo_url'  => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
