<?php

namespace Partymeister\Frontend\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class LoginPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'password' => 'required|string',
        ];
    }
}
