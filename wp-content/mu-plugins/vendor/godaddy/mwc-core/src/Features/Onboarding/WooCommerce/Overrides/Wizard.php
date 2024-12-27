<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\WooCommerce\Overrides;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\HomePage;
use function wc_is_running_from_async_action_scheduler;

class Wizard implements ComponentContract
{
    /** @var string transient flag name to redirect user to native onboarding wizard */
    const TRANSIENT_REDIRECT_TO_ONBOARDING_WIZARD = 'mwc_redirect_to_onboarding_wizard';

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function load()
    {
        $this->registerHooks();
    }

    /**
     * Register actions and filters hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'maybeSetRedirectToOnboardingWizardTransient'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_enable_setup_wizard')
            ->setHandler([$this, 'disableSetupWizard'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_prevent_automatic_wizard_redirect')
            ->setHandler([$this, 'disableWizardRedirect'])
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeRedirectToOnboardingWizard'])
            ->execute();
    }

    /**
     * Disables the setup Wizard.
     *
     * @internal
     *
     * @return bool
     */
    public function disableSetupWizard() : bool
    {
        return false;
    }

    /**
     * Disables automatic wizard redirection.
     *
     * @internal
     *
     * @return bool
     */
    public function disableWizardRedirect() : bool
    {
        return true;
    }

    /**
     * Sets a transient used to enable the redirect that takes users to the Managed WooCommerce Onboarding Wizard.
     *
     * @internal
     */
    public function maybeSetRedirectToOnboardingWizardTransient()
    {
        if (get_transient('_wc_activation_redirect')) {
            delete_transient('_wc_activation_redirect');

            set_transient(static::TRANSIENT_REDIRECT_TO_ONBOARDING_WIZARD, 1, 30);
        }
    }

    /**
     * Redirects request to Onboarding Wizard if the request meets the necessary conditions.
     *
     * @internal
     *
     * @throws Exception
     */
    public function maybeRedirectToOnboardingWizard()
    {
        if ($this->shouldRedirectToOnboardingWizard()) {
            $this->redirectToOnboardingWizard();
        }
    }

    /**
     * Redirects request to Onboarding Wizard.
     */
    protected function redirectToOnboardingWizard()
    {
        delete_transient(static::TRANSIENT_REDIRECT_TO_ONBOARDING_WIZARD);

        try {
            Redirect::to(admin_url('admin.php?page='.HomePage::SLUG.'&onboarding_step=start'))->execute();
        } catch (Exception $exception) {
            // we shouldn't be throwing an exception at this point since we are in a WordPress hook callback context
        }
    }

    /**
     * Determines whether it should redirect request to Onboarding Wizard or not.
     *
     * @return bool
     */
    protected function shouldRedirectToOnboardingWizard() : bool
    {
        return ! $this->isOnboardingWizardPage()
            && ! WordPressRepository::isAjax()
            && ! $this->isActivateMultiplePluginsRequest()
            && ! $this->isNetworkAdminRequest()
            && ! $this->isAsyncActionSchedulerRequest()
            && get_transient(static::TRANSIENT_REDIRECT_TO_ONBOARDING_WIZARD)
            && $this->canCurrentUserAccessHomePage();
    }

    /**
     * Determines whether the request comes from async action scheduler.
     *
     * @TODO: port this to WordPressRepository::isAsyncActionSchedulerRequest() helper method {nmolham 10-02-2022}.
     *
     * @return bool
     */
    protected function isAsyncActionSchedulerRequest() : bool
    {
        if (function_exists('wc_is_running_from_async_action_scheduler')) {
            return wc_is_running_from_async_action_scheduler();
        }

        return isset($_REQUEST['action']) && 'as_async_request_queue_runner' === $_REQUEST['action'];
    }

    /**
     * Determines whether the request is Network admin request or not.
     *
     * @TODO: port this to WordPressRepository::isNetworkAdmin() helper method {nmolham 10-02-2022}.
     *
     * @return bool
     */
    protected function isNetworkAdminRequest() : bool
    {
        return function_exists('is_network_admin') && is_network_admin();
    }

    /**
     * Determines whether the current logged-in user has access to Home page or not.
     *
     * @return bool
     */
    protected function canCurrentUserAccessHomePage() : bool
    {
        return function_exists('current_user_can') && current_user_can(HomePage::CAPABILITY);
    }

    /**
     * Determines whether the current page is the Onboarding Wizard or not.
     *
     * @return bool
     */
    protected function isOnboardingWizardPage() : bool
    {
        return isset($_REQUEST['page'], $_REQUEST['onboarding_step'])
            && HomePage::SLUG === $_REQUEST['page'];
    }

    /**
     * Determine whether the request is to activate multiple plugins or not.
     *
     * @return bool
     */
    protected function isActivateMultiplePluginsRequest() : bool
    {
        return isset($_GET['activate-multi']);
    }
}
