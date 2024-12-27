<?php

namespace GoDaddy\WordPress\MWC\Common\Http\Contracts;

interface IncomingRequestContract
{
    /**
     * Gets the HTTP request method.
     *
     * @return string
     */
    public function getMethod() : string;

    /**
     * Sets the HTTP request method.
     *
     * @param string $value
     * @return IncomingRequestContract
     */
    public function setMethod(string $value) : IncomingRequestContract;

    /**
     * Gets the request headers.
     *
     * @return array<string, string>
     */
    public function getHeaders() : array;

    /**
     * Sets the request headers.
     *
     * @param array<string, string> $value
     * @return IncomingRequestContract
     */
    public function setHeaders(array $value) : IncomingRequestContract;

    /**
     * Gets the request body.
     *
     * @return ?string
     */
    public function getBody() : ?string;

    /**
     * Sets the request body.
     *
     * @param string|null $value
     * @return IncomingRequestContract
     */
    public function setBody(?string $value) : IncomingRequestContract;
}
