<?php

namespace GoDaddy\WordPress\MWC\Common\Components\Contracts;

/**
 * A conditional component represents functionality that can be loaded only when certain conditions are met.
 */
interface ConditionalComponentContract extends ComponentContract
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * If false, the component will not even be instantiated.
     * @see \GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait::maybeInstantiateComponent()
     *
     * @return bool
     */
    public static function shouldLoad() : bool;
}
