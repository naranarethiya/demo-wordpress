<?php

namespace GoDaddy\WordPress\MWC\Common\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthMethodContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\GoDaddyRequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Traits\CanSetAuthMethodTrait;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

/**
 * Request handler for performing requests to GoDaddy.
 *
 * This class also wraps a Managed WooCommerce Site Token required by GoDaddy requests.
 *
 * @since 1.0.0
 */
class GoDaddyRequest extends Request implements GoDaddyRequestContract
{
    use CanSetAuthMethodTrait;

    /** @var string managed WooCommerce site token */
    public $siteToken;

    /** @var string Managed WooCommerce site's account UID */
    protected $accountUid;

    /** @var string locale */
    protected $locale;

    /**
     * Gets a new instance of the request after trying to set the authentication method.
     *
     * The current implementation returns an unmodified new instance of the class because the constructor already
     * tries to set the authentication method.
     *
     * In the future, once the rest of the codebase has been updated to use withAuth(), we will introduce a breaking
     * change to remove the logic to set the authentication method from the constructor of the class and add it to
     * this static constructor.
     *
     * @return static
     */
    public static function withAuth()
    {
        return new static();
    }

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->setHeaders()
            ->tryToSetAuthMethod()
            ->setLocale();
    }

    /**
     * Gets site's account UID.
     *
     * @deprecated since 4.3.0
     *
     * @return string|null
     */
    public function getAccountUid() : ?string
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '4.3.0');

        return $this->accountUid;
    }

    /**
     * Sets the site's account UID.
     *
     * @deprecated since 4.3.0
     *
     * @param string|null $accountUid
     *
     * @return GoDaddyRequest
     */
    public function setAccountUid(?string $accountUid = null) : GoDaddyRequest
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '4.3.0');

        $this->accountUid = $accountUid ?: Configuration::get('godaddy.account.uid', '');

        return $this;
    }

    /**
     * Builds a valid url string with parameters.
     *
     * @since 3.4.1
     *
     * @return string
     * @throws Exception
     */
    public function buildUrlString() : string
    {
        if ($this->locale) {
            if (empty($this->query)) {
                $this->query = [];
            }

            ArrayHelper::set($this->query, 'locale', $this->locale);
        }

        return parent::buildUrlString();
    }

    /**
     * Sets the current site API request token.
     *
     * @deprecated since 4.3.0
     *
     * @param string|null $token
     * @return GoDaddyRequest
     */
    public function siteToken(?string $token = null) : GoDaddyRequest
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '4.3.0');

        $this->siteToken = $token ?: Configuration::get('godaddy.site.token', 'empty');

        return $this;
    }

    /**
     * Sets the locale.
     *
     * @since 3.4.1
     *
     * @param string $locale
     * @return GoDaddyRequest
     */
    public function setLocale(string $locale = '') : GoDaddyRequest
    {
        if (empty($locale)) {
            $locale = WordPressRepository::getLocale();
        }

        $this->locale = $locale;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAuthMethodFromAuthProvider() : AuthMethodContract
    {
        return AuthProviderFactory::getNewInstance()->getManagedWooCommerceAuthProvider()->getMethod();
    }
}
