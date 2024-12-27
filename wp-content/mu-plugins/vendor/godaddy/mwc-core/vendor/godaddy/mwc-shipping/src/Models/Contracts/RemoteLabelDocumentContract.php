<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Contracts;

interface RemoteLabelDocumentContract extends LabelDocumentContract
{
    /**
     * Gets remote document's URL.
     *
     * @return string
     */
    public function getUrl() : string;

    /**
     * Sets remote document's URL.
     *
     * @param string $value
     * @return $this
     */
    public function setUrl(string $value);
}
