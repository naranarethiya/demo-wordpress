<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Sync\Jobs;

class CustomerPushJob extends AbstractPushJob
{
    /** {@inheritdoc} */
    protected $objectType = 'customer';
}
