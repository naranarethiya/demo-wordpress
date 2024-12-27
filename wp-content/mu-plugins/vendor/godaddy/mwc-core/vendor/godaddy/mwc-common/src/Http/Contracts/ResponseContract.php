<?php

namespace GoDaddy\WordPress\MWC\Common\Http\Contracts;

/**
 * Contract for HTTP Responses.
 */
interface ResponseContract
{
    /**
     * Sets the response body.
     *
     * @param array $value
     * @return $this
     */
    public function setBody(array $value) : ResponseContract;

    /**
     * Gets the response body.
     *
     * @return array|null
     */
    public function getBody() : ?array;

    /**
     * Sets the response status code.
     *
     * @param int $value
     * @return $this
     */
    public function setStatus(int $value = 200) : ResponseContract;

    /**
     * Gets the response status code.
     *
     * @return int|null
     */
    public function getStatus() : ?int;

    /**
     * Determines if the response is a success response.
     *
     * @return bool
     */
    public function isSuccess() : bool;

    /**
     * Determines if the response is an error response.
     *
     * @return bool
     */
    public function isError() : bool;

    /**
     * Gets the error message.
     *
     * @return string|null
     */
    public function getErrorMessage() : ?string;

    /**
     * Sends a response.
     *
     * @NOTE This will send a standard WP or API response back from the calling entity.
     *
     * @param bool $killAfter
     */
    public function send(bool $killAfter = true);
}
