<?php
namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClubRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'name'      => 'required|string|max:255',
            'logo'      => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'logo_url'  => 'nullable|string|max:255',
            'subdomain' => 'nullable|string',
        ];
    }
}
