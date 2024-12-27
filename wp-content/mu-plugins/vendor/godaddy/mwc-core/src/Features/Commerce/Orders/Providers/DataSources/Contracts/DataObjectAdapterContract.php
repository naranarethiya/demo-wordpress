<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts;

interface DataObjectAdapterContract
{
    /**
     * Converts from Data Source format.
     *
     * @param mixed $source
     * @return array|mixed
     */
    public function convertFromSource($source);

    /**
     * Converts to Data Source format.
     *
     * @param mixed $target
     * @return array|mixed
     */
    public function convertToSource($target);
}
