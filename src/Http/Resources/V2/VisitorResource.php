<?php

namespace Partymeister\Frontend\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseResource;
use Partymeister\Core\Models\Visitor;

/**
 * @mixin Visitor
 */
class VisitorResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'group' => $this->group,
            'country_iso_3166_1' => $this->country_iso_3166_1,
            'email' => $this->email,
            'additional_data' => $this->additional_data,
            'api_token' => $this->api_token,
        ];
    }
}
