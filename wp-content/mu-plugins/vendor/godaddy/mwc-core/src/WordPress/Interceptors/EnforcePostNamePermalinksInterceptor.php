<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

/**
 * Handles WordPress permalinks structure behavior.
 */
class EnforcePostNamePermalinksInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @throws Exception
     * @return void
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeSwitchToPostNamePermalinks'])
            ->execute();

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'maybeDisablePlainPermalinksOption'])
            ->execute();
    }

    /**
     * Load the interceptor, unless plain permalinks have been overridden by config.
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public static function shouldLoad() : bool
    {
        return ! Configuration::get('wordpress.permalinks.allowPlain', false)
            && PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlatformName() === 'woosaas';
    }

    /**
     * Display a notice and switch to post name permalinks if the current permalink structure is "plain".
     *
     * @internal
     *
     * @return void
     */
    public function maybeSwitchToPostNamePermalinks() : void
    {
        if ($this->isPlainPermalinkSet()) {
            $this->displayAdminNotice();
            $this->setPostNamePermalink();
        }
    }

    /**
     * Disables the "plain" permalinks option on the permalinks option page in wp-admin.
     *
     * @param mixed $hook
     * @throws Exception
     */
    public function maybeDisablePlainPermalinksOption($hook) : void
    {
        if ($hook !== 'options-permalink.php') {
            return;
        }

        Enqueue::script()
            ->setHandle('mwc-enforce-post-name-permalinks')
            ->setSource(WordPressRepository::getAssetsUrl('js/enforce-post-name-permalinks.js'))
            ->setVersion(TypeHelper::string(Configuration::get('mwc.version'), '1.0'))
            ->execute();
    }

    /**
     * Displays the plain permalinks admin notice.
     *
     * @return void
     */
    public function displayAdminNotice() : void
    {
        Notices::enqueueAdminNotice(Notice::getNewInstance()
            ->setId('mwc-plain-permalinks-enable')
            ->setType(Notice::TYPE_ERROR)
            ->setDismissible(false)
            ->setTitle($this->getPlainPermalinksAdminNoticeTitle())
            ->setContent($this->getPlainPermalinksAdminNoticeContent())
        );
    }

    /**
     * Gets the plain permalinks admin notice title.
     *
     * @return string
     */
    protected function getPlainPermalinksAdminNoticeTitle() : string
    {
        return __('Warning! Plain Permalinks Detected', 'mwc-core');
    }

    /**
     * Gets the plain permalinks admin notice content.
     *
     * @return string
     */
    protected function getPlainPermalinksAdminNoticeContent() : string
    {
        $body = esc_htmL__('Plain permalinks cause issues with some built-in features on your Managed WooCommerce Store. Your permalinks have been updated to use "post name".', 'mwc-core');
        $cta = '<a href="'.esc_url(SiteRepository::getAdminUrl('options-permalink.php')).'" class="button button-primary">'.esc_htmL__('View permalinks settings', 'mwc-core').'</a>';

        return "<p>{$body}</p><p>{$cta}</p>";
    }

    /**
     * Is plain permalink structure set to "plain".
     *
     * @return bool
     */
    protected function isPlainPermalinkSet() : bool
    {
        return empty(get_option('permalink_structure'));
    }

    /**
     * This method sets the permalink structure to post name.
     *
     * @return void
     */
    protected function setPostNamePermalink() : void
    {
        $index_php_prefix = '';

        if (! got_url_rewrite()) {
            $index_php_prefix = 'index.php';
        }

        update_option('permalink_structure', $index_php_prefix.'/%postname%/');
    }
}
