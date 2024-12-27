<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions\Contracts;

use ErrorException;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use Throwable;

/**
 * Exception handler contract.
 */
interface ExceptionHandlerContract
{
    /**
     * Deregisters the handler.
     *
     * @NOTE: Must restore the previous handlers.
     */
    public function deregisterHandler();

    /**
     * Initializes the handler.
     *
     * @NOTE: Must contain {@see set_exception_handler()} and {@see set_error_handler()}.
     */
    public function registerHandler();

    /**
     * The default method that handles PHP errors.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0);

    /**
     * The default method that handles PHP exceptions.
     *
     * @param Throwable $exception
     * @throws Exception|Throwable
     */
    public function handleException(Throwable $exception);

    /**
     * Adds an exception class name to the ignore list.
     *
     * @param string $class exception class
     * @return $this
     */
    public function ignore(string $class);

    /**
     * Method that actually reports the error.
     *
     * @param BaseException $exception
     * @throws Exception
     */
    public function report(BaseException $exception);
}
