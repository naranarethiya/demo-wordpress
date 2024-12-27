<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\CustomerPushInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanHandleWordPressDatabaseExceptionTrait;

class CommerceCustomerPush extends AbstractFeature
{
    use HasComponentsTrait;
    use CanHandleWordPressDatabaseExceptionTrait;

    /** @var string transient that disables the feature */
    public const TRANSIENT_DISABLE_FEATURE = 'godaddy_mwc_commerce_customer_push_disabled';

    /** @var class-string<ComponentContract>[] alphabetically ordered list of components to load */
    protected array $componentClasses = [
        CustomerPushInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce_customer_push';
    }

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        if (get_transient(static::TRANSIENT_DISABLE_FEATURE)) {
            return false;
        }

        if (! Commerce::getStoreId()) {
            return false;
        }

        return parent::shouldLoad();
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
}
