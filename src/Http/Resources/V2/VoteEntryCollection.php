<?php

namespace Partymeister\Frontend\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseCollection;

class VoteEntryCollection extends BaseCollection
{
    public $collects = VoteEntryResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
