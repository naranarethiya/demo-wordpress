<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\AttachmentsService;

/**
 * Event fired when attachment records are inserted into the local database, corresponding to remote assets.
 *
 * {@see AttachmentsService}
 *
 * @method static static getNewInstance(array $attachmentIds)
 */
class AttachmentsInsertedEvent implements EventContract
{
    use CanGetNewInstanceTrait;

    /** @var int[] local attachment IDs */
    public array $attachmentIds;

    /**
     * @param int[] $attachmentIds
     */
    public function __construct(array $attachmentIds)
    {
        $this->attachmentIds = $attachmentIds;
    }
}
