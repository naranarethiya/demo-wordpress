<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;

/**
 * The Delayable Email Contract.
 */
interface DelayableEmailContract extends EmailContract
{
    /**
     * Gets the timestamp the email should be sent at.
     *
     * @return int|null
     */
    public function getSendAt() : ?int;

    /**
     * Sets the email sentAt timestamp.
     *
     * @param int|null $value
     * @return $this
     */
    public function setSendAt(?int $value) : DelayableEmailContract;
}
