<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Contracts;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\AbstractAttachment;
use WP_Post;

/**
 * Contract for attachment adapters.
 *
 * @method static static getNewInstance(WP_Post $attachment)
 */
interface AttachmentAdapterContract extends DataSourceAdapterContract
{
    /**
     * Converts the attachment from source.
     *
     * @return AbstractAttachment
     */
    public function convertFromSource();
}
