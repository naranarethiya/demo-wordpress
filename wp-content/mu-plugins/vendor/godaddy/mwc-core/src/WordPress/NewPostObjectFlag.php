<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasPostMetaTrait;
use GoDaddy\WordPress\MWC\Core\WordPress\Traits\HasNewObjectFlagMetaTrait;

class NewPostObjectFlag
{
    use CanGetNewInstanceTrait;
    use HasNewObjectFlagMetaTrait;
    use HasPostMetaTrait;

    public function __construct(int $postId)
    {
        $this->objectId = $postId;

        $this->loadMeta('no');
    }
}
