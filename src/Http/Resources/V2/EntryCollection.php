<?php

namespace Partymeister\Frontend\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseCollection;

class EntryCollection extends BaseCollection
{
    public $collects = EntryResource::class;

    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
