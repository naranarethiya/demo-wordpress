<?php

namespace GoDaddy\WordPress\MWC\Common\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterAction;
use WP_Error;

/**
 * Helper class to record outgoing HTTP requests and their responses.
 *
 * Example usage to record all calls:
 *
 * ApiRequestRecorder::start();
 * $gateway->callApi(); // execute API request(s)
 * $apiRequests = ApiRequestRecorder::stop(); // array of logged outgoing API requests
 *
 * Example usage to record calls to URLs containing `/v1/commerce/proxy` only:
 *
 * ApiRequestRecorder::start(['/v1/commerce/proxy']);
 * $gateway->callApi(); // execute API request(s)
 * $apiRequests = ApiRequestRecorder::stop();
 */
class HttpRequestRecorder
{
    /** @var array<array<string, mixed>> recorded API requests */
    protected static array $requests = [];

    /** @var string[] array of strings that must be contained in the outgoing request URL */
    protected static array $requestUrlPathFilters = [];

    /**
     * Starts recording outgoing API requests.
     *
     * @param string[] $requestUrlPathFilters Optional: if provided, the outgoing request URL must contain all of the strings provided.
     *
     * @return void
     * @throws Exception
     */
    public static function start(array $requestUrlPathFilters = []) : void
    {
        // start with a fresh slate
        static::$requests = [];
        static::$requestUrlPathFilters = $requestUrlPathFilters;

        static::getHttpApiHook()->execute();
    }

    /**
     * Stops recording outgoing API requests, and returns the array of requests that had been recorded.
     *
     * After stopping, this also clears the array of API requests, meaning calling {@see static::getRecordedRequests()} immediately
     * after this will always return an empty array.
     *
     * @return array<array<string, mixed>>
     * @throws Exception
     */
    public static function stop() : array
    {
        static::getHttpApiHook()->deregister();

        $recordedRequests = static::getRecordedRequests();

        static::$requests = [];

        return $recordedRequests;
    }

    /**
     * Gets the API requests that have been recorded since the last start.
     *
     * @return array<array<string, mixed>>
     */
    public static function getRecordedRequests() : array
    {
        return static::$requests;
    }

    /**
     * Gets the hook callback.
     *
     * @return RegisterAction
     */
    protected static function getHttpApiHook() : RegisterAction
    {
        return Register::action()
            ->setGroup('http_api_debug')
            ->setHandler([HttpRequestRecorder::class, 'maybeRecordApiCalls'])
            ->setArgumentsCount(5);
    }

    /**
     * Hook callback for {@see static::getHttpApiHook()}.
     *
     * @internal
     *
     * @param array<mixed>|WP_Error $response HTTP response or WP_Error object.
     * @param string|mixed $context Context under which the hook is fired (e.g. `response`).
     * @param string|mixed $class HTTP transport used (e.g. `WpOrg\Requests\Requests`).
     * @param array<mixed>|mixed $passed_args HTTP request arguments.
     * @param string|mixed $url The request URL.
     * @return void
     */
    public static function maybeRecordApiCalls($response, $context, $class, $passed_args, $url) : void
    {
        if (! static::shouldRecordApiCall(TypeHelper::string($url, ''))) {
            return;
        }

        static::$requests[] = [
            'url'           => $url,
            'requestMethod' => ArrayHelper::get($passed_args, 'method', 'unknown'),
            'requestBody'   => static::maybeFormatJson(ArrayHelper::get($passed_args, 'body', 'n/a')),
            'responseCode'  => wp_remote_retrieve_response_code($response),
            'responseBody'  => static::maybeFormatJson(wp_remote_retrieve_body($response)),
        ];
    }

    /**
     * Determines whether the provided API call should be recorded.
     *
     * @param string $url Request URL.
     * @return bool
     */
    protected static function shouldRecordApiCall(string $url) : bool
    {
        if (empty(static::$requestUrlPathFilters)) {
            return true;
        }

        foreach (static::$requestUrlPathFilters as $filter) {
            if (mb_strpos($url, $filter) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Attempts to decode a JSON string. In the event that the supplied input is not a string or decoding fails, the original
     * string is returned unmodified.
     *
     * @param mixed $input
     * @return mixed|string
     */
    protected static function maybeFormatJson($input)
    {
        if (! is_string($input)) {
            return $input;
        }

        if ($decoded = json_decode($input)) {
            return $decoded;
        } else {
            return $input;
        }
    }

    /**
     * Writes the recorded requests to a log file.
     *
     * @param string $logFileName Name of the file to log requests to.
     * @return void
     */
    public static function logRecordedRequests(string $logFileName = 'gdMwcHttpRequests') : void
    {
        if ($recordedRequests = HttpRequestRecorder::getRecordedRequests()) {
            wc_get_logger()->warning(TypeHelper::string(json_encode($recordedRequests, JSON_PRETTY_PRINT), ''), ['source' => $logFileName]);
        }
    }
}
