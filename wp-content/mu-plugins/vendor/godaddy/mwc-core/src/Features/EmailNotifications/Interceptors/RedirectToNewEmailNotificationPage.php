<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Redirect;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

class RedirectToNewEmailNotificationPage extends AbstractInterceptor
{
    /**
     * Initialize the component.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('admin_page_access_denied')
            ->setHandler([$this, 'redirectToNewEmailNotificationsPage'])
            ->setCondition([$this, 'shouldRedirect'])
            ->execute();
    }

    /**
     * Redirect to new Email Notifications slug.
     *
     * @return void
     */
    public function redirectToNewEmailNotificationsPage()
    {
        try {
            $queryArgs = ArrayHelper::accessible($_GET) ? array_filter($_GET) : [];
            ArrayHelper::set($queryArgs, 'page', 'gd-email-notifications');

            Redirect::to(add_query_arg(
                $queryArgs,
                SiteRepository::getAdminUrl('admin.php')
            ))->execute();
        } catch (Exception $exception) {
            // do nothing.
        }
    }

    /**
     * Determines if it should redirect old Email Notifications slug to new slug.
     *
     * @return bool
     */
    public function shouldRedirect() : bool
    {
        return WordPressRepository::isAdmin()
            && 'godaddy-email-notifications' === ArrayHelper::get($_GET, 'page');
    }
}
