<?php

namespace Partymeister\Frontend\Http\Resources\V2;

use Motor\Admin\Helpers\Filesize;
use Motor\Admin\Http\Resources\MediaResource;
use Motor\Core\Http\Resources\V2\BaseResource;
use Partymeister\Competitions\Http\Resources\OptionResource;
use Partymeister\Competitions\Models\Entry;

/**
 * @mixin Entry
 */
class EntryResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'competition_name' => $this->competition->name,
            'title' => $this->title,
            'author' => $this->author,
            'filesize' => (int) $this->filesize,
            'filesize_human' => Filesize::bytesToHuman((int) $this->filesize),
            'platform' => $this->platform,
            'description' => $this->description,
            'organizer_description' => $this->organizer_description,
            'running_time' => $this->running_time,
            'options' => OptionResource::collection($this->options),
            'custom_option' => $this->custom_option,
            'author_name' => $this->author_name,
            'author_email' => $this->author_email,
            'author_phone' => $this->author_phone,
            'author_address' => $this->author_address,
            'author_zip' => $this->author_zip,
            'author_city' => $this->author_city,
            'author_country_iso_3166_1' => $this->author_country_iso_3166_1,
            'composer_name' => $this->composer_name,
            'composer_email' => $this->composer_email,
            'composer_phone' => $this->composer_phone,
            'composer_address' => $this->composer_address,
            'composer_zip' => $this->composer_zip,
            'composer_city' => $this->composer_city,
            'composer_country_iso_3166_1' => $this->composer_country_iso_3166_1,
            'discord_name' => $this->discord_name,
            'screenshot' => new MediaResource($this->getFirstMedia('screenshot')),
            'has_composer' => (bool) $this->competition->competition_type->has_composer,
            'has_screenshot' => (bool) $this->competition->competition_type->has_screenshot,
        ];
    }
}
