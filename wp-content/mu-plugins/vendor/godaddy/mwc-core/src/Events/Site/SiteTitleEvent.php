<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Site;

class SiteTitleEvent extends AbstractSiteOptionEvent
{
    /**
     * {@inheritDoc}
     */
    protected function resourceName() : string
    {
        return 'siteTitle';
    }
}
