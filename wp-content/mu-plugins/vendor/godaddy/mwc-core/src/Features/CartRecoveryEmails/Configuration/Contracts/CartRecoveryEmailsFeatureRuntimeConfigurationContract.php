<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Configuration\Contracts;

use GoDaddy\WordPress\MWC\Core\Configuration\Contracts\FeatureRuntimeConfigurationContract;

interface CartRecoveryEmailsFeatureRuntimeConfigurationContract extends FeatureRuntimeConfigurationContract
{
    /**
     * Get the number of cart recovery emails currently available on this site.
     *
     * @return int
     */
    public function getNumberOfCartRecoveryEmails() : int;

    /**
     * Is the email in the given position allowed, based on the number of cart recovery emails that are currently available on this site?
     *
     * @param int $messagePosition
     * @return bool
     */
    public function isCartRecoveryEmailAllowed(int $messagePosition) : bool;

    /**
     * Determines whether the delay for cart recovery email notifications should be read-only.
     *
     * @return bool
     */
    public function isDelayReadOnly() : bool;
}
