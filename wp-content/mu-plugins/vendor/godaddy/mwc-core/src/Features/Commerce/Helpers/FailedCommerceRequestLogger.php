<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\HttpRequestRecorder;

class FailedCommerceRequestLogger
{
    /**
     * Logs recorded failed Commerce requests.
     *
     * This is designed to be called after HTTP requests have started recording {@see HttpRequestRecorder::start()}, but before
     * they've been stopped {@see HttpRequestRecorder::stop()}. Technically request recording isn't required here, it just
     * helps provide more context behind _why_ the request might have failed. If requests have not been recorded, we'll
     * still log rudimentary failure information from the exception.
     *
     * The exception is expected to be thrown after a failed request, and that information is used to construct the logged error message.
     *
     * @NOTE This is a quick and temporary measure to aid in support debugging. In the future we aim to have more robust error handling/logging.
     *
     * @param Exception $exception
     * @param string $errorMessage
     * @param string $logFileName
     * @return void
     */
    public static function logFailedRequestFromException(Exception $exception, string $errorMessage = 'Failed Commerce request.', string $logFileName = 'gdCommerceRequestFailures') : void
    {
        $logger = wc_get_logger();

        $logger->warning(sprintf(
            '%s; Exception Class: %s; Exception Message: %s',
            $errorMessage,
            get_class($exception),
            $exception->getMessage()
        ), ['source' => $logFileName]);

        HttpRequestRecorder::logRecordedRequests($logFileName);

        // empty line
        $logger->warning('', ['source' => $logFileName]);
    }
}
