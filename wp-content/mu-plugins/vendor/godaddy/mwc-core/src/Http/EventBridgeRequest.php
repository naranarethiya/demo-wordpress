<?php

namespace GoDaddy\WordPress\MWC\Core\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Request;

/**
 * The event bridge request representation.
 *
 * @deprecated
 */
class EventBridgeRequest extends Request
{
    /** @var string the ID of the site */
    public $siteId;

    /**
     * Sets the site ID.
     *
     * @deprecated
     *
     * @param string $siteId the ID of the site
     * @return $this
     * @throws Exception
     */
    public function setSiteId(string $siteId) : Request
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.3.1');

        $this->siteId = $siteId;

        $this->headers($this->headers);

        return $this;
    }

    /**
     * Sets Request headers.
     *
     * @deprecated
     *
     * @param array|null $additionalHeaders
     * @return $this
     * @throws Exception
     */
    public function headers($additionalHeaders = []) : Request
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.3.1');

        parent::setHeaders(ArrayHelper::combine($additionalHeaders, ['X-Site-ID' => $this->siteId]));

        return $this;
    }
}
