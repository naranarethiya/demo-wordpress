<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;

/**
 * The Conditional Email Contract.
 */
interface ConditionalEmailContract extends EmailContract
{
    /**
     * Retrieves the send conditions.
     *
     * @return array
     */
    public function getConditions() : array;

    /**
     * Sets the send conditions.
     *
     * @param array $conditions
     * @return $this
     */
    public function setConditions(array $conditions) : ConditionalEmailContract;
}
