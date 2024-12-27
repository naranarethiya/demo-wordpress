<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\SkyVerge\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\MWC\Adapters\ExtensionAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;

/**
 * SkyVerge extension adapter.
 *
 * @since 1.0.0
 *
 * @deprecated Use \GoDaddy\WordPress\MWC\Common\DataSources\MWC\Adapters\ExtensionAdapter instead
 */
class SkyVergeExtensionAdapter extends ExtensionAdapter
{
    /**
     * Constructor.
     *
     * @param array $data data to be converted
     *
     * @deprecated
     */
    public function __construct(array $data)
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.4.1', '\GoDaddy\WordPress\MWC\Common\DataSources\MWC\Adapters\ExtensionAdapter');

        parent::__construct($data);
    }
}
