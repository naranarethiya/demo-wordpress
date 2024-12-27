<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\DelayedInstantiationComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Exceptions\InvalidActionException;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;

abstract class AbstractGoDaddyPaymentsNoticeInterceptor extends AbstractInterceptor implements DelayedInstantiationComponentContract
{
    /**
     * Schedules the interceptor to only instantiate in admin context, once WP is fully loaded.
     *
     * @param callable $callback
     * @throws Exception
     */
    public static function scheduleInstantiation(callable $callback) : void
    {
        if (! WordPressRepository::isAdmin() || WordPressRepository::isAjax()) {
            return;
        }

        Register::action()
            ->setGroup('init')
            ->setHandler($callback)
            ->execute();
    }

    /**
     * Determines whether the component should be loaded or not.
     *
     * @throws Exception
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (Worldpay::shouldLoad()) {
            return false;
        }

        if (ArrayHelper::get($_GET, 'onboardingError')) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidActionException|Exception
     */
    public function addHooks() : void
    {
        $action = Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'enqueueNotice']);

        /** @throws InvalidActionException {@see RegisterAction::execute()} really throws {@see InvalidActionException} instead of {@see Exception} */
        $action->execute();
    }

    /**
     * Enqueues a notice.
     */
    abstract public function enqueueNotice() : void;
}
