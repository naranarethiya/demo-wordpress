<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Models\AbstractAttachment;

/**
 * Exception thrown when an {@see AbstractAttachment} cannot be deleted.
 */
class AttachmentDeleteFailedException extends BaseException
{
}
