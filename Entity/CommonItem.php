<?php

namespace VisitMarche\ThemeTail\Entity;

use AcMarche\Pivot\Entities\Tag;

class CommonItem
{
    public ?string $id = null;
    public ?string $type = null;
    public ?string $url = null;
    public ?string $name = null;
    public ?string $description = null;
    public ?string $image = null;
    /**
     * @var array|Tag[]
     */
    public array $tags = [];

    public ?string $locality = null;
    public array $dateEvent = [];
    //for event
    public bool $isPeriod = false;

    public function __construct(
        string $type,
        string $id,
        string $name,
        ?string $description,
        ?string $image,
        ?string $url,
        array $tags
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->image = $image;
        $this->url = $url;
        $this->tags = $tags;
        $this->type = $type;
    }
}