<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Site;

class SiteLogoEvent extends AbstractSiteOptionEvent
{
    /**
     * {@inheritDoc}
     */
    protected function resourceName() : string
    {
        return 'siteLogo';
    }
}
