<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use WP_User;

/**
 * Disables plugin and theme uploads for trial plans.
 */
class DisableUploadsForTrialsInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('user_has_cap')
            ->setHandler([$this, 'maybeDisallowUploads'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(4)
            ->execute();
    }

    /**
     * Conditionally removes the capabilities to upload plugins and themes.
     *
     * @param array<string, bool> $allcaps Array of key/value pairs where keys represent a capability name and boolean values represent whether the user has that capability.
     * @param array<string> $caps Required primitive capabilities for the requested capability.
     * @param array<mixed> $args Arguments that accompany the requested capability check. `$args[0]` is the requested capability.
     * @param WP_User $user The user object.
     * @return array<string, bool>
     */
    public function maybeDisallowUploads($allcaps, $caps, $args, $user) : array
    {
        try {
            $requestedCapability = $args[0] ?? null;

            // If we're not requesting uploads, bail. This avoids an unnecessary trial check on unrelated capabilities.
            if (! in_array($requestedCapability, ['upload_plugins', 'upload_themes'], true)) {
                return $allcaps;
            }

            // @TODO Replace this check with a more generic plan permission check when that is added in MWC-8327. {agibson 2022-09-26}
            if (! PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlan()->isTrial()) {
                return $allcaps;
            }

            /*
             * `upload_plugins` and `upload_themes` are only real capabilities on multisite. On regular installs they get mapped
             * to the `install` equivalents. That means we actually have to unset the `install_` ones in order for the `upload_`
             * checks to return false.
             */

            if ('upload_plugins' === $requestedCapability) {
                $allcaps['install_plugins'] = false;
                $allcaps['upload_plugins'] = false;
            } elseif ('upload_themes' === $requestedCapability) {
                $allcaps['install_themes'] = false;
                $allcaps['upload_themes'] = false;
            }
        } catch (Exception $e) {
            // catch exceptions in a hook callback
        }

        return $allcaps;
    }
}
