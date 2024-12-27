<?php

declare(strict_types=1);

namespace GoDaddy\WordPress\MWC\Common\Vendor\League\Container\ServiceProvider;

use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\ContainerAwareInterface;
use IteratorAggregate;

interface ServiceProviderAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    public function add(ServiceProviderInterface $provider) : ServiceProviderAggregateInterface;

    public function provides(string $id) : bool;

    public function register(string $service) : void;
}
