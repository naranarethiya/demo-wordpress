<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Configuration\DisableIncompatibleFeaturesAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\PaymentSettingsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Locations\LocationsIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\OrdersIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanHandleWordPressDatabaseExceptionTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasCommerceCapabilitiesTrait;

class Commerce extends AbstractFeature
{
    use HasComponentsTrait;
    use CanHandleWordPressDatabaseExceptionTrait;
    use HasCommerceCapabilitiesTrait;

    public const CAPABILITY_READ = 'read';
    public const CAPABILITY_WRITE = 'write';
    public const CAPABILITY_EVENTS = 'events';
    public const CAPABILITY_DETECT_UPSTREAM_CHANGES = 'detect_upstream_changes';

    /** @var string transient that disables the feature */
    public const TRANSIENT_DISABLE_FEATURE = 'godaddy_mwc_commerce_disabled';

    /**
     * List of components to load.
     *
     * The list is separated in two groups by priority and the classes in each group are ordered alphabetically.
     *
     * @var class-string<ComponentContract>[]
     */
    protected array $componentClasses = [
        // these components should be loaded first
        CreateCommerceContextsTableAction::class,
        CreateCommerceMapResourceTypesTableAction::class,
        CreateCommerceMapIdsTableAction::class,
        CreateCommerceSkippedResourcesTableAction::class,
        InsertResourceTypesAction::class,
        CreateCommerceResourceUpdatesTableAction::class,

        // integrations
        CatalogIntegration::class,
        CustomersIntegration::class,
        InventoryIntegration::class,
        LocationsIntegration::class,
        OrdersIntegration::class,

        // misc.
        DisableIncompatibleFeaturesAction::class,
        PaymentSettingsInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce';
    }

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        if (get_transient(static::TRANSIENT_DISABLE_FEATURE)) {
            return false;
        }

        if (! static::getStoreId()) {
            return false;
        }

        if (static::isStagingSite()) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * Determines whether the site is a staging site.
     *
     * Assumes the site is a staging site if an error occurs trying to find out.
     *
     * @return bool
     */
    protected static function isStagingSite() : bool
    {
        try {
            return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isStagingSite();
        } catch (PlatformRepositoryException $exception) {
            return true;
        }
    }

    /**
     * Initializes the component.
     *
     * @throws Exception
     */
    public function load() : void
    {
        try {
            /** @throws WordPressDatabaseException|BaseException|Exception */
            $this->loadComponents();
        } catch (WordPressDatabaseException $exception) {
            $this->handleWordPressDatabaseException($exception, static::getName(), static::TRANSIENT_DISABLE_FEATURE);
        }
    }

    /**
     * Gets the store's ID.
     *
     * @return string|null
     */
    public static function getStoreId() : ?string
    {
        try {
            return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getStoreRepository()->getStoreId();
        } catch (PlatformRepositoryException $exception) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, bool>
     */
    public static function getCommerceCapabilities() : array
    {
        /** @var array<string, bool> $capabilities */
        $capabilities = TypeHelper::array(static::getConfiguration('capabilities', []), []);

        return $capabilities;
    }

    /**
     * Gets the channel ID.
     *
     * @return string
     */
    public static function getChannelId() : string
    {
        try {
            return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getChannelId();
        } catch (PlatformRepositoryException $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);

            return '';
        }
    }
}
