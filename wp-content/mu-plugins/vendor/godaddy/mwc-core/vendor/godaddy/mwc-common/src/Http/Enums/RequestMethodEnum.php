<?php

namespace GoDaddy\WordPress\MWC\Common\Http\Enums;

use GoDaddy\WordPress\MWC\Common\Traits\EnumTrait;

class RequestMethodEnum
{
    use EnumTrait;

    public const Get = 'GET';
    public const Post = 'POST';
    public const Put = 'PUT';
    public const Patch = 'PATCH';
    public const Delete = 'DELETE';
}
