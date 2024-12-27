<?php

namespace GoDaddy\WordPress\MWC\Common\Http;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlException;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlSchemeException;
use GoDaddy\WordPress\MWC\Common\Http\Url\QueryParameters;

/**
 * Object representation of a URL.
 */
class Url
{
    /** @var string */
    const SCHEME_HTTP = 'http';

    /** @var string */
    const SCHEME_HTTPS = 'https';

    /** @var string */
    protected $scheme = '';

    /** @var string */
    protected $host = '';

    /** @var int|null */
    protected $port;

    /** @var string */
    protected $path = '/';

    /** @var string */
    protected $fragment = '';

    /** @var QueryParameters|null */
    protected $queryParameters;

    /**
     * Constructor.
     *
     * @param string|null $url
     * @throws InvalidUrlException|InvalidUrlSchemeException
     */
    public function __construct(?string $url = null)
    {
        if (null === $url) {
            return;
        }

        $this->parseUrl($url);
    }

    /**
     * Parses a URL string to class properties.
     *
     * @param string $url
     * @return void
     * @throws InvalidUrlException|InvalidUrlSchemeException
     */
    protected function parseUrl(string $url) : void
    {
        if (! $parts = parse_url($url)) {
            throw new InvalidUrlException(sprintf('Invalid URL: %s', $url));
        }

        $scheme = ArrayHelper::get($parts, 'scheme', '');
        $this->scheme = ! empty($scheme) ? $this->sanitizeScheme($scheme) : '';

        $port = ArrayHelper::get($parts, 'port');
        $this->port = is_numeric($port) ? (int) $port : null;

        $this->host = ArrayHelper::get($parts, 'host', '');
        $this->path = ArrayHelper::get($parts, 'path', '/');
        $this->queryParameters = QueryParameters::fromString(ArrayHelper::get($parts, 'query', ''));
        $this->fragment = ArrayHelper::get($parts, 'fragment', '');
    }

    /**
     * Builds a new URL from a string.
     *
     * @param string $url
     * @return Url
     * @throws InvalidUrlException|InvalidUrlSchemeException
     */
    public static function fromString(string $url) : Url
    {
        return new Url($url);
    }

    /**
     * Gets valid schemes.
     *
     * @return string[]
     */
    protected function getValidSchemes() : array
    {
        return [
            static::SCHEME_HTTP,
            static::SCHEME_HTTPS,
        ];
    }

    /**
     * Sanitizes and validates a scheme.
     *
     * @param string $scheme
     * @return string
     * @throws InvalidUrlSchemeException
     */
    public function sanitizeScheme(string $scheme) : string
    {
        $scheme = strtolower($scheme);

        if (! in_array($scheme, $this->getValidSchemes(), true)) {
            throw new InvalidUrlSchemeException(sprintf('Invalid scheme: %s', $scheme));
        }

        return $scheme;
    }

    /**
     * Gets the scheme.
     *
     * @return string
     */
    public function getScheme() : string
    {
        return $this->scheme;
    }

    /**
     * Gets the authority.
     *
     * @return string
     */
    public function getAuthority() : string
    {
        $authority = $this->host;

        if (null !== $this->port) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    /**
     * Gets the host.
     *
     * @return string
     */
    public function getHost() : string
    {
        return $this->host;
    }

    /**
     * Gets the port.
     *
     * @return int|null
     */
    public function getPort() : ?int
    {
        return $this->port;
    }

    /**
     * Gets the path.
     *
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Gets the query parameters.
     *
     * @return QueryParameters|null
     */
    public function getQueryParameters() : ?QueryParameters
    {
        return $this->queryParameters;
    }

    /**
     * Gets a query parameter value by key.
     *
     * @param string $key query parameter key
     * @param mixed|null $default optional return value, defaults to null
     * @return mixed|null
     */
    public function getQueryParameter(string $key, $default = null)
    {
        return $this->queryParameters ? $this->queryParameters->get($key, $default) : $default;
    }

    /**
     * Gets the query.
     *
     * @return string
     */
    public function getQuery() : string
    {
        return $this->queryParameters ? $this->queryParameters->toString() : '';
    }

    /**
     * Gets the fragment.
     *
     * @return string|null
     */
    public function getFragment() : ?string
    {
        return $this->fragment;
    }

    /**
     * Sets the scheme.
     *
     * @param string $value
     * @return $this
     * @throws InvalidUrlSchemeException
     */
    public function setScheme(string $value) : Url
    {
        $this->scheme = $this->sanitizeScheme($value);

        return $this;
    }

    /**
     * Sets the host.
     *
     * @param string $value
     * @return $this
     */
    public function setHost(string $value) : Url
    {
        $this->host = $value;

        return $this;
    }

    /**
     * Sets the port.
     *
     * @param int $value
     * @return $this
     */
    public function setPort(int $value) : Url
    {
        $this->port = $value;

        return $this;
    }

    /**
     * Sets the path.
     *
     * @param string $value
     * @return Url
     */
    public function setPath(string $value) : Url
    {
        $this->path = $value;

        return $this;
    }

    /**
     * Sets the query parameters.
     *
     * @param QueryParameters $value
     * @return $this
     */
    public function setQueryParameters(QueryParameters $value) : Url
    {
        $this->queryParameters = $value;

        return $this;
    }

    /**
     * Determines if the URL has query parameters set and are not empty.
     *
     * @return bool
     */
    public function hasQueryParameters() : bool
    {
        return null !== $this->queryParameters && '' !== $this->queryParameters->toString();
    }

    /**
     * Add a query parameter.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addQueryParameter(string $key, $value) : Url
    {
        if (! $this->queryParameters) {
            $this->queryParameters = new QueryParameters();
        }

        $this->queryParameters->add($key, $value);

        return $this;
    }

    /**
     * Adds query parameters.
     *
     * @param array<string, mixed> $parameters
     * @return $this
     */
    public function addQueryParameters(array $parameters) : Url
    {
        if (! $this->queryParameters) {
            $this->queryParameters = new QueryParameters();
        }

        $this->queryParameters->addMany($parameters);

        return $this;
    }

    /**
     * Removes a query parameter.
     *
     * @param string $key
     * @return $this
     */
    public function removeQueryParameter(string $key) : Url
    {
        if (! $this->queryParameters) {
            return $this;
        }

        $this->queryParameters->remove($key);

        return $this;
    }

    /**
     * Removes query parameters.
     *
     * @param string[] $keys
     * @return $this
     */
    public function removeQueryParameters(array $keys) : Url
    {
        if (! $this->queryParameters) {
            return $this;
        }

        $this->queryParameters->remove($keys);

        return $this;
    }

    /**
     * Sets the fragment.
     *
     * @param string $fragment
     * @return $this
     */
    public function setFragment(string $fragment) : Url
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * Converts the URL object to a URL string.
     *
     * @return string
     */
    public function toString() : string
    {
        $url = '';

        if ('' !== $this->getScheme()) {
            $url .= $this->getScheme().'://';
        }

        if ('' !== $this->getAuthority()) {
            $url .= $this->getAuthority();
        }

        if ('/' !== $this->getPath()) {
            $url .= $this->getPath();
        }

        if ('' !== $this->getQuery()) {
            $url .= '?'.$this->getQuery();
        }

        if ('' !== $this->getFragment()) {
            $url .= '#'.$this->getFragment();
        }

        return $url;
    }

    /**
     * Implements the magic method to convert the URL to a string.
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->toString();
    }
}
