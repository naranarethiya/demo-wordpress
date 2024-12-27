<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

class EventBridgeEventActionEnum
{
    use EnumTrait;

    /** @var string represents a new object got created. ex: a new order created */
    public const Create = 'create';

    /** @var string represents an object got updated. ex: a page got updated */
    public const Update = 'update';

    /** @var string represents an object get deleted. ex: a product got deleted */
    public const Delete = 'delete';

    /** @var string represents an object get customized (in a generic way). ex: a theme got customized */
    public const Customize = 'customize';
}
