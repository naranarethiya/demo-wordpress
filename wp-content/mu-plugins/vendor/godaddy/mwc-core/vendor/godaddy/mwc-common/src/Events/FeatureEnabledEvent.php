<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

/**
 * Feature enabled event class.
 */
class FeatureEnabledEvent extends AbstractFeatureEvent
{
    /**
     * FeatureEnabledEvent constructor.
     *
     * @param string $featureId
     */
    public function __construct(string $featureId)
    {
        parent::__construct($featureId);

        $this->action = 'enable';
    }
}
