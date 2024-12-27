<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\LabelDocumentContract;

class AbstractLabelDocument extends AbstractModel implements LabelDocumentContract
{
    /** @var string document's format */
    protected $format;

    /**
     * {@inheritDoc}
     */
    public function getFormat() : string
    {
        return $this->format;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormat(string $value)
    {
        $this->format = $value;

        return $this;
    }
}
