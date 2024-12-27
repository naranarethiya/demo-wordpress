<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\LabelStatusContract;

/**
 * Status for processing labels.
 */
class ProcessingLabelStatus implements LabelStatusContract
{
    use HasLabelTrait;
    use CanConvertToArrayTrait;

    /**
     * Initializes the status by setting its name and label.
     */
    public function __construct()
    {
        $this->setName('processing');
        $this->setLabel(__('Processing', 'mwc-shipping'));
    }
}
