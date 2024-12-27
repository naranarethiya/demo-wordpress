<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

/**
 * Feature disabled event class.
 */
class FeatureDisabledEvent extends AbstractFeatureEvent
{
    /**
     * FeatureDisabledEvent constructor.
     *
     * @param string $featureId
     */
    public function __construct(string $featureId)
    {
        parent::__construct($featureId);

        $this->action = 'disable';
    }
}
