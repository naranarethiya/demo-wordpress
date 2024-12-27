<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

class PackageType extends AbstractModel implements PackageTypeContract
{
    protected string $code = '';

    protected string $name = '';

    protected string $description = '';

    /**
     * {@inheritDoc}
     */
    public function setCode(string $value)
    {
        $this->code = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCode() : string
    {
        return $this->code;
    }

    /**
     * {@inheritDoc}
     */
    public function setName(string $value)
    {
        $this->name = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setDescription(string $value)
    {
        $this->description = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription() : string
    {
        return $this->name;
    }
}
