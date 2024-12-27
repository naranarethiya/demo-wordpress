<?php

namespace GoDaddy\WordPress\MWC\Common\Platforms\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\GoDaddyCustomerContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Stores\Contracts\StoreRepositoryContract;

/**
 * Platform Repository Contract.
 */
interface PlatformRepositoryContract
{
    /**
     * Gets the GoDaddy customer ID.
     *
     * @return string
     */
    public function getGoDaddyCustomerId() : string;

    /**
     * Gets the GoDaddy customer object.
     *
     * @return GoDaddyCustomerContract
     */
    public function getGoDaddyCustomer() : GoDaddyCustomerContract;

    /**
     * Gets an object representation of the hosting plan used by this site.
     *
     * @return HostingPlanContract
     */
    public function getPlan() : HostingPlanContract;

    /**
     * Retrieves the name of the platform in a "slug" format. This is expected to be passed along in X-Source headers.
     *
     * @return string
     */
    public function getPlatformName() : string;

    /**
     * Retrieves the "raw", unmodified ID of the site. This value is unique per-platform, but not necessarily unique across different platforms.
     * For example: there will only be one site with ID `123` on MWP, but there may be a separate site on WooSaaS that also has the same ID `123`.
     *
     * @return string
     */
    public function getPlatformSiteId() : string;

    /**
     * Gets the configured reseller account ID, if present.
     *
     * @return int|null
     */
    public function getResellerId() : ?int;

    /**
     * Determines if the site is sold by a reseller.
     *
     * @return bool
     */
    public function isReseller() : bool;

    /**
     * Retrieves the ID of the site, and may include some kind of prefix to make it unique across different platforms.
     * e.g. if {@see PlatformRepositoryContract::getPlatformSiteId()} returns `123`, then this method may return `woosaas_123`.
     *
     * @return string
     */
    public function getSiteId() : string;

    /**
     * Gets the venture ID.
     *
     * @return string
     */
    public function getVentureId() : string;

    /**
     * Determines if the site is has an eCommerce plan.
     *
     * @return bool
     */
    public function hasEcommercePlan() : bool;

    /**
     * Determines if we have the data that we expect a valid site on this platform to have.
     *
     * @return bool
     */
    public function hasPlatformData() : bool;

    /**
     * Determines if the current site is a staging site.
     *
     * @return bool
     */
    public function isStagingSite() : bool;

    /**
     * Determines if the host represents a temporary domain.
     *
     * @return bool
     */
    public function isTemporaryDomain() : bool;

    /**
     * Determines if the site is originating from an internal TLA account.
     *
     * @return bool
     */
    public function isTlaSite() : bool;

    /**
     * Gets the channel ID.
     *
     * @return string
     */
    public function getChannelId() : string;

    /**
     * Gets the list of blocked plugin directory names.
     *
     * @return string[]
     */
    public function getBlockedPlugins() : array;

    /**
     * Gets the platform environment instance.
     *
     * @return PlatformEnvironmentContract
     */
    public function getPlatformEnvironment() : PlatformEnvironmentContract;

    /**
     * Gets the store repository for the platform.
     *
     * @return StoreRepositoryContract
     */
    public function getStoreRepository() : StoreRepositoryContract;
}
