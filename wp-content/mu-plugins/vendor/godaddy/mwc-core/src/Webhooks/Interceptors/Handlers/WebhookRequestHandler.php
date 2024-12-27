<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\Handlers;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Pipeline\Pipeline;
use GoDaddy\WordPress\MWC\Common\Schedule\Exceptions\InvalidScheduleException;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Webhooks\Adapters\WebhookAdapter;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\IncomingWebhookRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\InvalidWebhookNamespaceException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\InvalidWebhookRequestMethodException;
use GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\ProcessWebhookJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Webhooks\Interceptors\WebhookRequestInterceptor;
use GoDaddy\WordPress\MWC\Core\Webhooks\Middleware\Contracts\WebhookMiddlewareContract;
use GoDaddy\WordPress\MWC\Core\Webhooks\Repositories\WebhooksRepository;
use Throwable;

/**
 * Handler for {@see WebhookRequestInterceptor}.
 */
class WebhookRequestHandler extends AbstractInterceptorHandler
{
    protected WebhooksRepository $webhooksRepository;

    public function __construct(WebhooksRepository $webhooksRepository)
    {
        $this->webhooksRepository = $webhooksRepository;
    }

    /**
     * Callback for the `parse_request` action {@see WebhookRequestInterceptor::addHooks()}.
     *
     * This reroutes request that contain our `mwc-webhooks` query arg with a valid namespace.
     * i.e. `https://example.org?mwc-webhooks=commerce`
     *
     * @param ...$args
     *
     * @return void
     */
    public function run(...$args) : void
    {
        $requestMethod = StringHelper::upperCase(SanitizationHelper::slug($_SERVER['REQUEST_METHOD'] ?? null));
        $namespace = StringHelper::lowerCase(SanitizationHelper::slug($_REQUEST['mwc-webhooks'] ?? ''));

        if ($namespace) {
            $this->handleWebhookRequest($requestMethod, $namespace);
        }
    }

    /**
     * Validates and handles the webhook request.
     *
     * @param string $method
     * @param string $namespace
     * @return void
     */
    protected function handleWebhookRequest(string $method, string $namespace) : void
    {
        try {
            $this->setNoCacheHeaders();
            $this->setNoCacheConstants();

            $this->validateWebhookRequest($method, $namespace);

            $this->processValidWebhook($method, $namespace);

            // 202 means it's been accepted but not processed yet. We set this since processing happens async.
            status_header(202);

            $this->dieWithMessage('Accepted');
        } catch(Throwable $e) {
            status_header($e->getCode() ?: 500);

            $this->dieWithMessage($e->getMessage());
        }
    }

    /**
     * Exits the script with the given message.
     *
     * @codeCoverageIgnore
     *
     * @param string $message
     * @return void
     */
    protected function dieWithMessage(string $message) : void
    {
        die($message);
    }

    /**
     * Validates that we actually support this method + namespace combination.
     * We do not validate the payload itself during this period.
     *
     * @param string $method
     * @param string $namespace
     * @return void
     * @throws InvalidWebhookNamespaceException|InvalidWebhookRequestMethodException
     */
    protected function validateWebhookRequest(string $method, string $namespace) : void
    {
        $webhookSettings = Configuration::get("webhooks.endpoints.{$namespace}");

        // make sure it's a registered webhook type
        if (! $webhookSettings) {
            throw new InvalidWebhookNamespaceException();
        }

        // make sure provided method is allowed; POST is allowed by default
        $allowedMethods = ArrayHelper::wrap(Configuration::get("webhooks.endpoints.{$namespace}.methods", ['POST']));
        if (! in_array($method, $allowedMethods)) {
            throw new InvalidWebhookRequestMethodException();
        }
    }

    /**
     * Further processes a valid webhook.
     *
     * - Builds an object containing the headers and payload;
     * - Runs through middleware for this namespace (this may then reject the request);
     * - Saves the payload in the database;
     * - Schedules an async job to actually handle the webhook.
     *
     * @param string $method
     * @param string $namespace
     * @return void
     * @throws Throwable
     */
    public function processValidWebhook(string $method, string $namespace) : void
    {
        // each namespace can supply their own middleware to validate the webhook
        $webhookRequest = $this->pipeRequestThroughMiddleware(
            $this->makeIncomingRequestObject($method),
            $namespace
        );

        try {
            // store the webhook payload in the local database
            $webhookAutoIncId = $this->webhooksRepository->addWebhook(
                WebhookAdapter::getNewInstance()->convertFromSource($webhookRequest, $namespace)
            );

            $this->scheduleBackgroundJobToProcessWebhook($webhookAutoIncId, $namespace);
        } catch (WordPressDatabaseException $exception) {
            if (stripos($exception->getMessage(), 'Duplicate entry') === false || stripos($exception->getMessage(), 'webhook_id') === false) {
                throw $exception;
            }
        }
    }

    /**
     * Makes an {@see IncomingWebhookRequest} object from the request data.
     *
     * @param string $method
     * @return IncomingWebhookRequest
     */
    protected function makeIncomingRequestObject(string $method) : IncomingWebhookRequest
    {
        /** @var IncomingWebhookRequest $request -- phpstan doesn't recognize the concrete */
        $request = IncomingWebhookRequest::getNewInstance()
            ->setMethod($method)
            ->setHeaders($this->getRequestHeaders())
            ->setBody($this->getRequestPayload());

        return $request;
    }

    /**
     * Sends the incoming request through the namespace middleware.
     *
     * @param IncomingWebhookRequest $incomingRequest
     * @param string $namespace
     * @return IncomingWebhookRequest
     * @throws Throwable
     */
    protected function pipeRequestThroughMiddleware(IncomingWebhookRequest $incomingRequest, string $namespace) : IncomingWebhookRequest
    {
        $middleware = TypeHelper::arrayOfClassStrings(
            Configuration::get("webhooks.endpoints.{$namespace}.middleware"),
            WebhookMiddlewareContract::class,
            false
        );

        if (! $middleware) {
            return $incomingRequest;
        }

        /** @var IncomingWebhookRequest $incomingRequest */
        $incomingRequest = Pipeline::getNewInstance()
            ->send($incomingRequest)
            ->through($middleware)
            ->thenReturn();

        return $incomingRequest;
    }

    /**
     * Schedules a background job to process the provided webhook.
     *
     * @param int $webhookAutoIncId
     * @param string $namespace
     * @return void
     * @throws InvalidScheduleException
     */
    protected function scheduleBackgroundJobToProcessWebhook(int $webhookAutoIncId, string $namespace) : void
    {
        $job = Schedule::singleAction()
            ->setName(ProcessWebhookJobInterceptor::JOB_NAME)
            ->setArguments($webhookAutoIncId, $namespace)
            ->setScheduleAt(new DateTime('now'))
            ->setPriority(20);

        if (! $job->isScheduled()) {
            $job->schedule();
        }
    }

    /**
     * Sets the HTTP headers to prevent caching for the different browsers.
     *
     * @return void
     */
    protected function setNoCacheHeaders() : void
    {
        nocache_headers();
    }

    /**
     * Defines constants to prevent caching.
     *
     * @return void
     */
    protected function setNoCacheConstants() : void
    {
        foreach (['DONOTCACHEPAGE', 'DONOTCACHEOBJECT', 'DONOTCACHEDB'] as $constant) {
            if (! defined($constant)) {
                define($constant, true);
            }
        }
    }

    /**
     * Gets the request headers.
     *
     * @return array<string, string>
     */
    protected function getRequestHeaders() : array
    {
        return ArrayHelper::where(ArrayHelper::wrap($_SERVER), function ($value, $key) {
            return is_string($key) &&
                is_string($value) &&
                (
                    StringHelper::startsWith($key, 'HTTP_') ||
                    StringHelper::startsWith($key, 'CONTENT_')
                );
        });
    }

    /**
     * Gets the request payload.
     *
     * @codeCoverageIgnore
     *
     * @return string|null
     */
    protected function getRequestPayload() : ?string
    {
        return file_get_contents('php://input') ?: null;
    }
}
