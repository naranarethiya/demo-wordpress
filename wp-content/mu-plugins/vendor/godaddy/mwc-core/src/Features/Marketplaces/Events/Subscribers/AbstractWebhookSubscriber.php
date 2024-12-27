<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Events\AbstractWebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Common\Events\Subscribers\AbstractWebhookReceivedSubscriber;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\Marketplaces\Webhook\Methods\SignatureHeader;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\Marketplaces\Webhook\Models\Credentials;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\AbstractWebhookPayload;

/**
 * The base class for Marketplaces webhook subscribers.
 */
abstract class AbstractWebhookSubscriber extends AbstractWebhookReceivedSubscriber
{
    /** @var string the webhook type handled by a concrete subscriber */
    protected string $webhookType = '';

    /**
     * Handles the event.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @return bool
     * @throws PlatformRepositoryException
     */
    public function validate(AbstractWebhookReceivedEvent $event) : bool
    {
        $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();
        $channelId = $platformRepository->getChannelId();
        $ventureId = $platformRepository->getVentureId();

        if (empty($channelId) || empty($ventureId)) {
            return false;
        }

        if (! StringHelper::isJson($event->getPayload())) {
            return false;
        }

        $credentials = (new Credentials())->setChannelId($channelId)->setVentureId($ventureId);
        $signature = SignatureHeader::getNewInstance($credentials)->getSignature($event->getPayload());
        $header = TypeHelper::string(ArrayHelper::get($event->getHeaders(), SignatureHeader::HEADER_NAME), '');

        return ! empty($header) && hash_equals($signature, $header);
    }

    /**
     * Gets the configured webhook payload adapter for the supplied webhook type.
     *
     * @param array<string, mixed> $payload
     * @return DataSourceAdapterContract
     * @throws SentryException
     */
    protected function getWebhookPayloadAdapter(array $payload) : DataSourceAdapterContract
    {
        if (empty($this->webhookType)) {
            throw new SentryException(sprintf('No webhook type configured for the subscriber %s.', __CLASS__));
        }

        $className = TypeHelper::string(Configuration::get("marketplaces.webhooks.adapters.{$this->webhookType}"), '');

        if (empty($className)) {
            throw new SentryException("No {$this->webhookType} webhook adapter configured.");
        }

        if (! class_exists($className)) {
            throw new SentryException("{$this->webhookType} adapter class {$className} does not exist.");
        }

        $classInterfaces = class_implements($className);

        if (! is_array($classInterfaces) || ! in_array(DataSourceAdapterContract::class, $classInterfaces, true)) {
            throw new SentryException("{$className} must implement DataSourceAdapterContract");
        }

        return $className::getNewInstance($payload);
    }

    /**
     * Gets the webhook payload object for the event.
     *
     * @param AbstractWebhookReceivedEvent $event
     * @return AbstractWebhookPayload|null
     * @throws SentryException
     */
    protected function getWebhookPayload(AbstractWebhookReceivedEvent $event) : ?AbstractWebhookPayload
    {
        $webhookPayload = ! empty($this->webhookType) ? $this->getWebhookPayloadAdapter($event->getPayloadDecoded())->convertFromSource() : null;

        return $webhookPayload instanceof AbstractWebhookPayload && $webhookPayload->isExpectedEvent() ? $webhookPayload : null;
    }
}
