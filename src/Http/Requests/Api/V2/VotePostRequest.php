<?php

namespace Partymeister\Frontend\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class VotePostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vote_category_id' => 'required|integer|exists:vote_categories,id',
            'points' => 'integer',
            'comment' => 'nullable|string',
            'special_vote' => 'nullable|boolean',
            'live' => 'nullable|boolean',
        ];
    }
}
