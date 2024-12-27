<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Request as GraphQLRequest;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Request;

/**
 * Request handler for performing requests to GoDaddy.
 *
 * This class also wraps a Managed WooCommerce Site Token required by GoDaddy requests.
 *
 * @deprecated
 */
class MessagesRequest extends Request
{
    /** @var string managed WooCommerce auth token */
    public $authToken;

    /** @var string managed WooCommerce auth token type */
    public $authTokenType;

    /** @var string managed WooCommerce site token */
    public $siteToken;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        DeprecationHelper::deprecatedClass(__CLASS__, 'auth-providers', GraphQLRequest::class);

        $this->setAuthToken()
            ->setAuthTokenType()
            ->setSiteToken()
            ->headers([
                'Authorization' => "{$this->authTokenType} {$this->authToken}",
                'X-Site-Token'  => $this->siteToken,
            ]);
    }

    /**
     * Sets the current site Auth token.
     *
     * @since 1.0.0
     *
     * @param string|null $token
     * @return MessagesRequest
     * @throws Exception
     */
    public function setAuthToken($token = null) : MessagesRequest
    {
        DeprecationHelper::deprecatedClass(__METHOD__, 'auth-providers');

        $this->authToken = $token ?: Configuration::get('messages.api.auth.token', 'empty');

        return $this;
    }

    /**
     * Sets the current site Auth token type.
     *
     * @since 1.0.0
     *
     * @param string|null $type
     * @return MessagesRequest
     * @throws Exception
     */
    public function setAuthTokenType($type = null) : MessagesRequest
    {
        DeprecationHelper::deprecatedClass(__METHOD__, 'auth-providers');

        $this->authTokenType = $type ?: Configuration::get('messages.api.auth.type', 'Bearer');

        return $this;
    }

    /**
     * Sets the current site API request token.
     *
     * @since 1.0.0
     *
     * @param string|null $token
     * @return MessagesRequest
     * @throws Exception
     */
    public function setSiteToken($token = null) : MessagesRequest
    {
        DeprecationHelper::deprecatedClass(__METHOD__, 'auth-providers');

        $this->siteToken = $token ?: Configuration::get('godaddy.site.token', 'empty');

        return $this;
    }
}
