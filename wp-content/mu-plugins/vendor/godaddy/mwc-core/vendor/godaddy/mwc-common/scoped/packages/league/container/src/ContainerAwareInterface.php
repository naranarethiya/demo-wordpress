<?php

declare(strict_types=1);

namespace GoDaddy\WordPress\MWC\Common\Vendor\League\Container;

interface ContainerAwareInterface
{
    public function getContainer() : DefinitionContainerInterface;

    public function setContainer(DefinitionContainerInterface $container) : ContainerAwareInterface;
}
