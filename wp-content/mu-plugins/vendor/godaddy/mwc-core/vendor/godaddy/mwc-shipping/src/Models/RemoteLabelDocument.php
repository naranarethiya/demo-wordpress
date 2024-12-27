<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\RemoteLabelDocumentContract;

class RemoteLabelDocument extends AbstractLabelDocument implements RemoteLabelDocumentContract
{
    /** @var string the document's URL */
    protected $url;

    /**
     * {@inheritdoc}
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    public function setUrl(string $value)
    {
        $this->url = $value;

        return $this;
    }
}
