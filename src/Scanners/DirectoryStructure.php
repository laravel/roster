<?php

namespace Laravel\Using\Scanners;

use Laravel\Using\Approach;
use Laravel\Using\Enums\Approaches;

class DirectoryStructure
{
    public function __construct(protected string $path) {}

    public function scan()
    {
        $items = collect();

        $actions = $this->path.DIRECTORY_SEPARATOR.'/app/'.DIRECTORY_SEPARATOR.'Actions';
        if (is_dir($actions)) {
            $items->push(new Approach(Approaches::ACTION));
        }

        $domains = $this->path.DIRECTORY_SEPARATOR.'/app/'.DIRECTORY_SEPARATOR.'Domains';
        if (is_dir($domains)) {
            $items->push(new Approach(Approaches::DDD));
        }

        return $items;
    }
}
