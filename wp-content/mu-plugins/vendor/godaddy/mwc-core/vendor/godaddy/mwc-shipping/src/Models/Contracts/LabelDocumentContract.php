<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

interface LabelDocumentContract extends ModelContract
{
    /**
     * Gets the document format.
     *
     * @return string
     */
    public function getFormat() : string;

    /**
     * Sets the document format.
     *
     * @param string $value
     * @return $this
     */
    public function setFormat(string $value);
}
