<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\LabelStatusContract;

/**
 * Status for completed labels.
 */
class CompletedLabelStatus implements LabelStatusContract
{
    use HasLabelTrait;
    use CanConvertToArrayTrait;

    /**
     * Initializes the status by setting its name and label.
     */
    public function __construct()
    {
        $this->setName('completed');
        $this->setLabel(__('Completed', 'mwc-shipping'));
    }
}
