<?php

namespace GoDaddy\WordPress\MWC\Common\Migrations\Contracts;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;

/**
 * A migration represents a routine that's responsible for executing scripts that can act on the database or
 * anything that's necessary to set up the plugin after a version change.
 */
interface MigrationContract extends ComponentContract
{
}
