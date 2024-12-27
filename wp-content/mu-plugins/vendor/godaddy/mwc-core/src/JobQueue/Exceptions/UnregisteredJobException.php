<?php

namespace GoDaddy\WordPress\MWC\Core\JobQueue\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * Exception thrown when a job is missing from the `queue.jobs` config array.
 */
class UnregisteredJobException extends BaseException
{
}
