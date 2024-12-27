<?php

declare(strict_types=1);

namespace GoDaddy\WordPress\MWC\Common\Vendor\League\Container\Inflector;

use GoDaddy\WordPress\MWC\Common\Vendor\League\Container\ContainerAwareInterface;
use IteratorAggregate;

interface InflectorAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    public function add(string $type, callable $callback = null) : Inflector;

    public function inflect(object $object);
}
