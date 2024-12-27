<?php

namespace GoDaddy\WordPress\MWC\Common\Container;

use GoDaddy\WordPress\MWC\Common\Container\Adapters\LeagueContainer\ContainerAdapter;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ContainerContract;

/**
 * MWC dependency injection container.
 *
 * Keep this class empty. Container code changes should happen in the extended class.
 *
 * For maintainability, this only extends a class that implements {@see ContainerContract}.
 * If MWC ever needs to switch to another DI container library (external or its own), create a new container adapter
 * class for the other library, then extend that new class here. Code depending on this Container won't need any changes.
 */
class Container extends ContainerAdapter
{
}
