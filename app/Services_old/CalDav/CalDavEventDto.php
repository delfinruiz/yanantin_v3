<?php

namespace App\Services\CalDav;

class CalDavEventDto
{
    public function __construct(
        public string $title,
        public ?string $description,
        public \DateTimeInterface $startsAt,
        public ?\DateTimeInterface $endsAt,
        public bool $allDay = false,
        public ?string $color = null,
        public ?string $uid = null,
        public ?string $etag = null,
    ) {}
}
