<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\LabelStatusContract;

/**
 * Status for error labels.
 */
class ErrorLabelStatus implements LabelStatusContract
{
    use HasLabelTrait;
    use CanConvertToArrayTrait;

    /**
     * Initializes the status by setting its name and label.
     */
    public function __construct()
    {
        $this->setName('error');
        $this->setLabel(__('Error', 'mwc-shipping'));
    }
}
