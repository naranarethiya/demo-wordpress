<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Request;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Traits\CanGetMerchantAccountIdentifierTrait;

/**
 * Marketplaces request class.
 */
class GoDaddyMarketplacesRequest extends Request
{
    use CanGetNewInstanceTrait;
    use CanGetMerchantAccountIdentifierTrait;

    /** @var string partner value to include in the request body and route */
    protected const PARTNER = 'gdwoo';

    /** @var string request route - do not include a slash at the start */
    protected $route = '';

    /**
     * Constructor.
     *
     * @throws AuthProviderException|CredentialsCreateFailedException|PlatformRepositoryException|Exception
     */
    public function __construct()
    {
        $apiUrl = TypeHelper::string(Configuration::get('marketplaces.api.url'), '');

        parent::__construct(
            StringHelper::trailingSlash($apiUrl).'v2/partner/'.static::PARTNER.'/'.$this->route
        );

        $this->setAuthMethod(AuthProviderFactory::getNewInstance()->getMarketplacesAuthProvider()->getMethod());

        $this->addHeaders([
            'X-Customer-Id' => $this->getMerchantAccountIdentifier(),
        ])->setMethod('POST');
    }

    /**
     * {@inheritDoc}
     */
    public function send()
    {
        if ($bodyData = $this->buildBodyData()) {
            $this->setBody($bodyData);
        }

        return parent::send();
    }

    /**
     * Builds the request body.
     *
     * @return array<string, mixed>
     */
    protected function buildBodyData() : array
    {
        return [];
    }
}
