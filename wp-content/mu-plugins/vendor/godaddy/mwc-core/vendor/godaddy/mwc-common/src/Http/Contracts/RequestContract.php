<?php

namespace GoDaddy\WordPress\MWC\Common\Http\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthMethodContract;

/**
 * Contract for HTTP Requests.
 */
interface RequestContract
{
    /**
     * Builds a valid url string with parameters.
     *
     * @return string
     * @throws Exception
     */
    public function buildUrlString() : string;

    /**
     * Sets the request method.
     *
     * @param string|null $method
     * @return $this
     */
    public function setMethod(?string $method = null) : RequestContract;

    /**
     * Sends the request.
     *
     * @return ResponseContract
     * @throws Exception
     */
    public function send();

    /**
     * Sets the body of the request.
     *
     * @param array<string, mixed> $body
     * @return $this
     */
    public function setBody(array $body) : RequestContract;

    /**
     * Sets Request headers.
     *
     * @param array<string, mixed>|null $additionalHeaders
     * @return $this
     */
    public function setHeaders(?array $additionalHeaders = []) : RequestContract;

    /**
     * Merges the provided Request headers with the headers already set.
     *
     * @param array<string, mixed> $additionalHeaders
     * @return $this
     */
    public function addHeaders(array $additionalHeaders) : RequestContract;

    /**
     * Sets query parameters.
     *
     * @param array<mixed>|null $params
     * @return $this
     */
    public function setQuery(?array $params = []) : RequestContract;

    /**
     * Sets the request timeout.
     *
     * @param int $seconds
     * @return $this
     */
    public function setTimeout(int $seconds = 30) : RequestContract;

    /**
     * Sets the url of the request.
     *
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url) : RequestContract;

    /**
     * Sets SSL verify.
     *
     * @param bool $default
     * @return $this
     */
    public function sslVerify(bool $default = false) : RequestContract;

    /**
     * Sets the auth method for this request.
     *
     * @param AuthMethodContract $value the auth method to set
     * @return $this
     */
    public function setAuthMethod(AuthMethodContract $value);

    /**
     * Gets the authentication method for this request.
     *
     * @return AuthMethodContract|null auth method instance if it is set, otherwise null
     */
    public function getAuthMethod() : ?AuthMethodContract;

    /**
     * Sets the request path.
     *
     * @param string $value
     * @return $this
     */
    public function setPath(string $value) : RequestContract;

    /**
     * Gets the request path.
     *
     * @return string|null
     */
    public function getPath() : ?string;

    /**
     * Validates the request.
     *
     * @return void|mixed
     * @throws Exception
     */
    public function validate();
}
