<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\LabelStatusContract;

/**
 * Status for voided labels.
 */
class VoidedLabelStatus implements LabelStatusContract
{
    use HasLabelTrait;
    use CanConvertToArrayTrait;

    /**
     * Initializes the status by setting its name and label.
     */
    public function __construct()
    {
        $this->setName('voided');
        $this->setLabel(__('Voided', 'mwc-shipping'));
    }
}
