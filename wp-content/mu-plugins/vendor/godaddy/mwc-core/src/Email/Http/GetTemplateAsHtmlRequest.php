<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Request;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Core\Events\Http\GraphQL\Queries\TemplateAsHtmlQuery;

/**
 * A request used to convert MJML templates into HTML.
 *
 * @deprecated use {@see Request} and {@see TemplateAsHtmlQuery}
 *
 * @method self url(string $value)
 * @method self setMethod(string $value)
 * @method self headers(array $additionalHeaders)
 * @method self setSiteId(string $siteId)
 */
class GetTemplateAsHtmlRequest
{
    /** @var string MJML template */
    protected $template = '';

    /** @var array variables for the MJML template */
    protected $templateParameters = [];

    /**
     * Gets the MJML template for this request.
     *
     * @deprecated
     *
     * @return string
     */
    public function getTemplate() : string
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.3.1');

        return $this->template;
    }

    /**
     * Gets the parameters for the template associated with this request.
     *
     * @deprecated
     *
     * @return array
     */
    public function getTemplateParameters() : array
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.3.1');

        return $this->templateParameters;
    }

    /**
     * Sets the MJML template for this request.
     *
     * @deprecated
     *
     * @param string $value the MJML template
     * @return self
     */
    public function setTemplate(string $value) : GetTemplateAsHtmlRequest
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.3.1');

        $this->template = $value;

        return $this;
    }

    /**
     * Sets the parameters for the template associated with this request.
     *
     * @deprecated
     *
     * @param array $value an array of parameters
     * @return self
     */
    public function setTemplateParameters(array $value) : GetTemplateAsHtmlRequest
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.3.1');

        $this->templateParameters = $value;

        return $this;
    }

    /**
     * Sends the request.
     *
     * @deprecated
     *
     * @return Response|null
     * @throws Exception
     */
    public function send() : ?Response
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.3.1');

        return null;
    }

    /**
     * Gets the GraphQL query for the request.
     *
     * @deprecated
     *
     * @return string
     */
    protected function getQuery() : string
    {
        return <<<'GQL'
query ($template: String, $templateParametersJson: String, $templateEncoded: String, $templateParametersJsonEncoded: String){
  templateAsHtml(template: $template, templateParametersJson: $templateParametersJson, templateEncoded: $templateEncoded, templateParametersJsonEncoded: $templateParametersJsonEncoded ) {
    html
  }
}
GQL;
    }

    /**
     * Gets the variables for the request.
     *
     * @deprecated
     *
     * @return array
     */
    protected function getVariables() : array
    {
        return [
            'templateEncoded'               => base64_encode($this->getTemplate()),
            'templateParametersJsonEncoded' => base64_encode(json_encode($this->getTemplateParameters()) ?: ''),
        ];
    }
}
