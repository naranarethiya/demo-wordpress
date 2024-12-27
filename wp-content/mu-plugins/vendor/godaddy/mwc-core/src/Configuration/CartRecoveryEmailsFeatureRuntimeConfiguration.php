<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Configuration\Contracts\CartRecoveryEmailsFeatureRuntimeConfigurationContract;

class CartRecoveryEmailsFeatureRuntimeConfiguration extends FeatureRuntimeConfiguration implements CartRecoveryEmailsFeatureRuntimeConfigurationContract
{
    /** @var HostingPlanContract */
    protected HostingPlanContract $hostingPlan;

    /** @var string Override property value with cart_recovery_emails */
    protected string $featureName = 'cart_recovery_emails';

    public function __construct(HostingPlanContract $hostingPlan)
    {
        $this->hostingPlan = $hostingPlan;
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfCartRecoveryEmails() : int
    {
        if ($this->hostingPlan->isTrial()) {
            return 3;
        }

        return TypeHelper::int(
            ArrayHelper::get(
                [
                    'essentials'        => 1,
                    'essentialsCA'      => 1,
                    'essentials_GDGCPP' => 1,
                    'flex'              => 2,
                    'flexCA'            => 2,
                    'flex_GDGCPP'       => 2,
                    'expand'            => 3,
                    'expandCA'          => 3,
                    'expand_GDGCPP'     => 3,
                    'premier'           => 3,
                ],
                $this->hostingPlan->getName(),
            ), 1
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isCartRecoveryEmailAllowed(int $messagePosition) : bool
    {
        return $this->getNumberOfCartRecoveryEmails() >= $messagePosition;
    }

    /**
     * {@inheritDoc}
     */
    public function isDelayReadOnly() : bool
    {
        return $this->isManagedWooCommerceStoresPlan();
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription() : string
    {
        return $this->isManagedWooCommerceStoresPlan() ? __('Automatically track cart activity and recover lost revenue with a dedicated email campaign.', 'mwc-core') : parent::getDescription();
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentationUrl() : string
    {
        return $this->isManagedWooCommerceStoresPlan() ? 'https://www.godaddy.com/help/a-41331' : parent::getDocumentationUrl();
    }

    /**
     * Determines if the current hosting plan is a Managed WooCommerce Store plan.
     *
     * @return bool
     */
    protected function isManagedWooCommerceStoresPlan() : bool
    {
        return ArrayHelper::contains(
            [
                'essentials',
                'essentialsca',
                'essentials_gdgcpp',
                'flex',
                'flexca',
                'flex_gdgcpp',
                'expand',
                'expandca',
                'expand_gdgcpp',
                'premier',
            ],
            StringHelper::lowerCase($this->hostingPlan->getName())
        );
    }
}
