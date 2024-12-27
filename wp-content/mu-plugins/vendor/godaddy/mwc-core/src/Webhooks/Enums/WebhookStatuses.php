<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Enums;

use GoDaddy\WordPress\MWC\Common\Traits\EnumTrait;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;

/**
 * Received webhook statuses. {@see Webhook}.
 */
class WebhookStatuses
{
    use EnumTrait;

    /** @var string Webhook has been received and is queued for processing. */
    public const Queued = 'queued';

    /** @var string Webhook has been successfully processed. */
    public const Completed = 'completed';

    /** @var string Webhook processing failed. */
    public const Failed = 'failed';
}
