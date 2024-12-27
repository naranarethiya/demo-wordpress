<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices as AdminNotices;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Interceptors\AutoConnectInterceptor;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Business;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;

/**
 * Class Notices.
 *
 * TODO: consider converting this class into a general notice handler (rendering and Ajax) for core notices {@wvega 2021-05-28}
 */
class Notices
{
    /** @var string action used to dismiss a notice */
    const ACTION_DISMISS_NOTICE = 'mwc_dismiss_notice';

    /** @var array sections to display GoDaddy Payment Recommendation */
    const GDP_RECOMMENDATION_SECTIONS = ['local_pickup_plus', 'cod'];

    /** @var array tabs to display GoDaddy Payment Recommendation */
    const GDP_RECOMMENDATION_TABS = ['shipping'];

    /** @var string WC Local Pickup Shipping Method id */
    const WC_LOCAL_PICKUP = 'local_pickup';

    /** @var array registered admin notices */
    protected $notices = [];

    /**
     * Notices constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->registerHooks();
    }

    /**
     * Registers the hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'registerNotices'])
            ->execute();

        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'renderNotices'])
            ->execute();
    }

    /**
     * Renders the notices.
     *
     * @throws Exception
     */
    public function renderNotices()
    {
        if (! $user = User::getCurrent()) {
            return;
        }

        foreach ($this->notices as $data) {
            if (! $this->shouldRenderNotice($user, $data)) {
                continue;
            }

            $this->renderNotice($data);
        }
    }

    /**
     * Determines whether a notice should be rendered for the given user.
     *
     * @param User $user a user object
     * @param array $data notice data
     * @return bool
     */
    public function shouldRenderNotice(User $user, array $data) : bool
    {
        // bail if notice is not dismissible or if the notice was not dismissed by the user
        return ! ArrayHelper::get($data, 'dismissible', true)
            || ! AdminNotices::isNoticeDismissed($user, ArrayHelper::getStringValueForKey($data, 'id', ''));
    }

    /**
     * Renders a notice.
     *
     * @param array $data
     * @throws Exception
     */
    protected function renderNotice(array $data)
    {
        if (empty($data['message'])) {
            return;
        }

        $classes = ArrayHelper::combine([
            'notice',
            'notice-'.ArrayHelper::get($data, 'type', 'info'),
        ], ArrayHelper::wrap(ArrayHelper::get($data, 'classes', [])));

        if (! empty($data['dismissible'])) {
            $classes[] = 'is-dismissible';
        } ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-message-id="<?php echo esc_attr(ArrayHelper::get($data, 'id', '')); ?>"><p><?php echo wp_kses_post($data['message']); ?></p></div>
        <?php
    }

    /**
     * Adds a notice for display.
     *
     * @param array $data
     */
    protected function registerNotice(array $data)
    {
        if (empty($data['id'])) {
            return;
        }

        $this->notices[$data['id']] = $data;
    }

    /**
     * Registers the notices that should be displayed.
     *
     * TODO: this method definitely needs to be broken up, and hopefully removed if we reactify these notices {@cwiseman 2021-05-24}
     *
     * @throws Exception
     */
    public function registerNotices() : void
    {
        if (Worldpay::shouldLoad()) {
            return;
        }

        $this->maybeRegisterConnectedAccountNotice();
    }

    /**
     * Determines whether the GoDaddy Payments gateway is enabled.
     *
     * We need to check the configuration value when the notices are being registered to make sure we catch the new settings values after the form in the settings page is saved.
     *
     * @return bool
     * @throws Exception
     */
    protected function isGatewayEnabled() : bool
    {
        // TODO: update the provider name if we rename poynt to godaddy-payments or something else {@wvega 2021-05-29}
        return Configuration::get('payments.poynt.enabled', false);
    }

    /**
     * Determines whether the GoDaddy Payments Sell in Person gateway is enabled.
     *
     * @return bool
     * @throws Exception
     */
    protected function isSiPGatewayEnabled() : bool
    {
        return (bool) Configuration::get('payments.godaddy-payments-payinperson.enabled', false);
    }

    /**
     * Determines whether the BOPIT feature is active.
     *
     * @return bool
     * @throws Exception
     */
    public static function isBOPITFeatureEnabled() : bool
    {
        return Configuration::get('features.bopit', false);
    }

    /**
     * Registers the notice for a connected GDP account.
     *
     * @throws Exception
     */
    protected function maybeRegisterConnectedAccountNotice()
    {
        if (true !== Configuration::get('features.gdp_by_default.enabled')) {
            return;
        }

        if (! $this->shouldShowGDPConnectionNotices()) {
            return;
        }

        if (! $business = $this->getConnectedBusiness()) {
            return;
        }

        $this->registerNotice([
            'dismissible' => true,
            'id'          => Onboarding::hasSwitchedAccounts() ? 'mwc-payments-godaddy-payments-connection-switched' : 'mwc-payments-godaddy-payments-connection',
            'message'     => $this->getConnectionNoticeMessage($business),
            'type'        => 'success',
        ]);
    }

    /**
     * Determines if the GDP connection notices should be displayed for the current page load.
     *
     * This is currently limited to:
     * - WooCommerce -> Settings -> General
     * - WooCommerce -> Settings -> Payments
     * - WooCommerce -> Settings -> Payments -> GoDaddy Payments
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldShowGDPConnectionNotices() : bool
    {
        if ('wc-settings' !== ArrayHelper::get($_GET, 'page')) {
            return false;
        }

        $tab = ArrayHelper::get($_GET, 'tab');

        if ($tab && ! ArrayHelper::contains(['general', 'checkout'], $tab)) {
            return false;
        }

        $section = ArrayHelper::get($_GET, 'section');

        if ($section && 'poynt' !== $section) {
            return false;
        }

        return Poynt::isConnected() && AutoConnectInterceptor::wasConnected();
    }

    /**
     * Gets the connected business.
     *
     * @return Business|null
     */
    protected function getConnectedBusiness() : ?Business
    {
        try {
            return Poynt::getBusiness();
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * Gets the connection notice message.
     *
     * @param Business $business
     *
     * @return string
     * @throws Exception
     */
    protected function getConnectionNoticeMessage(Business $business) : string
    {
        // display a special message after switch
        if (Onboarding::hasSwitchedAccounts()) {
            return sprintf(
                /* translators: Placeholders: %1$s - a connected account's legal name, %2$s - a connected account's email address, %3$s - <a> tag, %4$s - </a> */
                __('GoDaddy Payments is now connected with the account %1$s %2$s. (Not right? %3$sSwitch account%4$s.)', 'mwc-core'),
                $business->getDoingBusinessAs(),
                $business->getEmailAddress(),
                '<a href="'.esc_url(OnboardingEventsProducer::getSwitchStartUrl()).'">',
                '</a>'
            );
        }

        // otherwise, display the newly connected message
        $message = '<strong>'.__('You\'re all set to take payments with GoDaddy Payments!', 'mwc-core').'</strong>';

        if (Onboarding::getRequiredActions()) {
            $message .= ' '.sprintf(
                /* translators: Placeholders: %1$s - <a> tag, %2$s - </a> */
                __('To get your funds deposited to your bank account, verify your identity and add your banking info. %1$sSet up payouts%2$s', 'mwc-core'),
                '<a href="'.esc_url(Onboarding::getApplicationUrl()).'" target="_blank">',
                '</a>'
            );
        }

        $message .= '<br /><br />'.sprintf(
            /* translators: Placeholders: %1$s - a connected account's legal name, %2$s - a connected account's email address, %3$s - <a> tag, %4$s - </a> */
            __('The connected GoDaddy Payments account is %1$s %2$s (Not the business owner\'s account? %3$sSwitch account%4$s).', 'mwc-core'),
            $business->getDoingBusinessAs(),
            $business->getEmailAddress(),
            '<a href="'.esc_url(OnboardingEventsProducer::getSwitchStartUrl()).'">',
            '</a>'
        );

        return $message;
    }
}
