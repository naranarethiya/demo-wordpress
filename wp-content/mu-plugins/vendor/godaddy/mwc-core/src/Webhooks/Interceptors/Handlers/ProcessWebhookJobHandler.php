<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Webhooks\Enums\WebhookStatuses;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\InvalidWebhookHandlerException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\InvalidWebhookRowIdException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Handlers\Contracts\WebhookHandlerContract;
use GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\ProcessWebhookJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;

/**
 * Handler for {@see ProcessWebhookJobInterceptor}.
 *
 * This class is responsible for receiving a webhook ID (local database auto-inc ID) and namespace. The processing of
 * this webhook then needs to be routed to the correct handler for that namespace, as per the `webhooks.php` config.
 */
class ProcessWebhookJobHandler extends AbstractInterceptorHandler
{
    protected WebhooksRepository $webhooksRepository;

    public function __construct(WebhooksRepository $webhooksRepository)
    {
        $this->webhooksRepository = $webhooksRepository;
    }

    /**
     * @param ...$args
     *
     * @return void
     */
    public function run(...$args)
    {
        $webhookAutoIncId = TypeHelper::int(ArrayHelper::get($args, 0), 0);
        $namespace = TypeHelper::string(ArrayHelper::get($args, 1), '');

        try {
            $handler = $this->getHandler($namespace);

            if (! $webhook = $this->webhooksRepository->getWebhook($webhookAutoIncId)) {
                throw new InvalidWebhookRowIdException("No webhook record found for ID: {$webhookAutoIncId}");
            }

            $handler->handle($webhook);

            if ($webhook->id) {
                $this->updateWebhookRecord($webhook->id, WebhookStatuses::Completed);
            }
        } catch(Exception $exception) {
            $this->updateWebhookRecord($webhookAutoIncId, WebhookStatuses::Failed, $exception->getMessage());

            SentryException::getNewInstance('Failed to process webhook: '.$exception->getMessage(), $exception);
        }
    }

    /**
     * Update the webhook database record.
     *
     * @param int $webhookAutoIncId
     * @param string $status
     * @param string|null $result
     * @return void
     */
    protected function updateWebhookRecord(int $webhookAutoIncId, string $status, ?string $result = '')
    {
        try {
            $this->webhooksRepository->updateProcessedStatus($webhookAutoIncId, $status, $result);
        } catch (Exception $exception) {
            SentryException::getNewInstance('Failed to update webhook record: '.$exception->getMessage(), $exception);
        }
    }

    /**
     * Gets the concrete handler instance for the provided namespace.
     *
     * @param string $namespace
     * @return WebhookHandlerContract
     * @throws InvalidWebhookHandlerException|ContainerException|EntryNotFoundException
     */
    protected function getHandler(string $namespace) : WebhookHandlerContract
    {
        return ContainerFactory::getInstance()->getSharedContainer()->get($this->getHandlerClassName($namespace));
    }

    /**
     * Gets the handler class name from config.
     *
     * @param string $namespace
     * @return class-string<WebhookHandlerContract>
     * @throws InvalidWebhookHandlerException
     */
    protected function getHandlerClassName(string $namespace) : string
    {
        $handlerClassName = TypeHelper::string(Configuration::get("webhooks.endpoints.{$namespace}.handler", ''), '');

        if (is_a($handlerClassName, WebhookHandlerContract::class, true)) {
            return $handlerClassName;
        }

        throw new InvalidWebhookHandlerException("No valid handler found for {$namespace}.");
    }
}
