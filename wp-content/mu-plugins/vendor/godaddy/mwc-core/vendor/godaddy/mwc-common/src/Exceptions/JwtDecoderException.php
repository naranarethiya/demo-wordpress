<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions;

class JwtDecoderException extends BaseException
{
    /** @var int exception code */
    protected $code = 401;
}
