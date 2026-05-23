<?php
namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClubRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|unique:clubs,subdomain',
        ];
    }
}
