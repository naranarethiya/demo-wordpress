<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanHandleWordPressDatabaseExceptionTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IntegrationEnabledOnTestTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Actions\CreateCommerceWebhookSubscriptionsTableAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\CreateWebhookSubscriptionJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\DeleteWebhookSubscriptionJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\UpdateWebhookSubscriptionJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\WebhookSubscriptionsInterceptor;

class CommerceWebhooks extends AbstractFeature
{
    use HasComponentsFromContainerTrait;
    use CanHandleWordPressDatabaseExceptionTrait;
    use IntegrationEnabledOnTestTrait;

    /** @var string transient that disables the feature */
    public const TRANSIENT_DISABLE_FEATURE = 'godaddy_mwc_commerce_webhooks_disabled';

    /** @var class-string[] alphabetically ordered list of components to load */
    protected array $componentClasses = [
        CreateCommerceWebhookSubscriptionsTableAction::class,
        CreateWebhookSubscriptionJobInterceptor::class,
        DeleteWebhookSubscriptionJobInterceptor::class,
        UpdateWebhookSubscriptionJobInterceptor::class,
        WebhookSubscriptionsInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce_webhooks';
    }

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        if (get_transient(static::TRANSIENT_DISABLE_FEATURE)) {
            return false;
        }

        return parent::shouldLoad();
    }

    /**
     * Loads the feature.
     *
     * @return void
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException|Exception
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
