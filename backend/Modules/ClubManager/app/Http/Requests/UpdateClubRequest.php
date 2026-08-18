<?php
namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'name'      => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
