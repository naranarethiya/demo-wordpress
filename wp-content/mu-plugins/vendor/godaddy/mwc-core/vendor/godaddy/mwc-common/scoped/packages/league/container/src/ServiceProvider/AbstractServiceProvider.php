<?php

declare(strict_types=1);

namespace GoDaddy\WordPress\MWC\Common\Vendor\League\Container\ServiceProvider;

use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\ContainerAwareTrait;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;
    /**
     * @var string
     */
    protected $identifier;

    public function getIdentifier() : string
    {
        return $this->identifier ?? \get_class($this);
    }

    public function setIdentifier(string $id) : ServiceProviderInterface
    {
        $this->identifier = $id;

        return $this;
    }
}
