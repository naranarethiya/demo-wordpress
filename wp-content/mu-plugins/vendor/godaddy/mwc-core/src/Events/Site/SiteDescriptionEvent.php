<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Site;

class SiteDescriptionEvent extends AbstractSiteOptionEvent
{
    /**
     * {@inheritDoc}
     */
    protected function resourceName() : string
    {
        return 'siteDescription';
    }
}
