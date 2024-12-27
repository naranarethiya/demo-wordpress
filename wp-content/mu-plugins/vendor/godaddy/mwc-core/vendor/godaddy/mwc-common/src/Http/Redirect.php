<?php

namespace GoDaddy\WordPress\MWC\Common\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlException;

/**
 * Redirection handler.
 */
class Redirect
{
    /**
     * The query parameters.
     *
     * @var array<string, mixed>
     */
    public $queryParameters;

    /**
     * the path to redirect to.
     *
     * @todo remove in the next major version {nmolham 13-12-2021}
     *
     * @deprecated use method setLocation.
     *
     * @var string|null
     */
    public $path;

    /**
     * The location to redirect to.
     *
     * @var string|null
     */
    protected $location;

    /**
     * The redirect status code.
     *
     * @var int
     */
    protected $statusCode = 302;

    /**
     * The X-Redirect-By header.
     *
     * @var string
     */
    protected $redirectBy = 'Managed WooCommerce';

    /**
     * Determines should use safe redirect.
     *
     * @var bool
     */
    protected $safe = true;

    /**
     * Redirect constructor.
     *
     * @param string|null $location
     */
    public function __construct(?string $location = null)
    {
        if ($location) {
            $this->setLocation($location);
        }
    }

    /**
     * Builds a valid url string with parameters.
     *
     * @return string
     * @throws InvalidUrlException
     */
    protected function buildUrlString() : string
    {
        $location = $this->getLocation() ?: $this->path;

        if (! $location) {
            throw new InvalidUrlException('A valid url was not given for the requested redirect');
        }

        $queryString = ! empty($this->queryParameters) ? '?'.ArrayHelper::query($this->queryParameters) : '';

        return "{$location}{$queryString}";
    }

    /**
     * Executes the redirect.
     *
     * @return void
     * @throws Exception
     */
    public function execute() : void
    {
        if ($this->redirect()) {
            exit;
        }
    }

    /**
     * Redirects to another page.
     *
     * @return bool
     * @throws Exception
     */
    protected function redirect() : bool
    {
        $wpFunction = $this->isSafe() ? 'wp_safe_redirect' : 'wp_redirect';

        return function_exists($wpFunction) &&
            (bool) $wpFunction($this->buildUrlString(), $this->getStatusCode(), $this->getRedirectBy());
    }

    /**
     * Sets the redirect path.
     *
     * @todo remove in the next major version {nmolham 13-12-2021}
     *
     * @deprecated use method setLocation.
     *
     * @param string $path
     * @return Redirect
     */
    public function setPath(string $path) : Redirect
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Sets the query parameters.
     *
     * @param array<string, mixed> $params
     * @return Redirect
     */
    public function setQueryParameters(array $params) : Redirect
    {
        $this->queryParameters = $params;

        return $this;
    }

    /**
     * Gets the query parameters.
     *
     * @return array<string, mixed>|null
     */
    public function getQueryParameters() : ?array
    {
        return $this->queryParameters;
    }

    /**
     * Gets redirect status code.
     *
     * @return int
     */
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    /**
     * Sets redirect status code.
     *
     * @param int $statusCode
     * @return Redirect
     */
    public function setStatusCode(int $statusCode) : Redirect
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Gets the X-Redirect-By header.
     *
     * @return string
     */
    public function getRedirectBy() : string
    {
        return $this->redirectBy;
    }

    /**
     * Sets the X-Redirect-By header.
     *
     * @param string $redirectBy
     * @return Redirect
     */
    public function setRedirectBy(string $redirectBy) : Redirect
    {
        $this->redirectBy = $redirectBy;

        return $this;
    }

    /**
     * Determines if it should safely execute the redirect or not.
     *
     * @return bool
     */
    public function isSafe() : bool
    {
        return $this->safe;
    }

    /**
     * Sets safe redirect status.
     *
     * @param bool $safe
     * @return Redirect
     */
    public function setSafe(bool $safe) : Redirect
    {
        $this->safe = $safe;

        return $this;
    }

    /**
     * Creates a Redirect instance with the given location.
     *
     * @param string|null $location
     * @return Redirect
     */
    public static function to(?string $location = null) : Redirect
    {
        return new Redirect($location);
    }

    /**
     * Gets the location to redirect to.
     *
     * @return string|null
     */
    public function getLocation() : ?string
    {
        return $this->location;
    }

    /**
     * Sets the location to redirect to.
     *
     * @param string $location
     * @return Redirect
     */
    public function setLocation(string $location) : Redirect
    {
        $this->location = $location;

        return $this;
    }
}
