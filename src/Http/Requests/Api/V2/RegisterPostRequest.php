<?php

namespace Partymeister\Frontend\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:4',
            'access_key' => 'required|string',
            'group' => 'nullable|string|max:255',
            'country_iso_3166_1' => 'nullable|string|max:2',
        ];
    }
}
