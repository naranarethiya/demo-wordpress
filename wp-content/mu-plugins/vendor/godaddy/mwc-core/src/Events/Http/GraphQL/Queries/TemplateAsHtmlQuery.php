<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

class TemplateAsHtmlQuery extends AbstractGraphQLOperation
{
    /** @var string GraphQL operation */
    protected $operation = <<<'GQL'
query (
  $template: String
  $templateParametersJson: String
  $templateEncoded: String
  $templateParametersJsonEncoded: String
) {
  templateAsHtml(
    template: $template
    templateParametersJson: $templateParametersJson
    templateEncoded: $templateEncoded
    templateParametersJsonEncoded: $templateParametersJsonEncoded
  ) {
    html
  }
}
GQL;

    /** @var string the MJML template */
    protected $template = '';

    /** @var array the parameters for the template */
    protected $templateParameters = [];

    /**
     * Gets the MJML template.
     *
     * @return string
     */
    public function getTemplate() : string
    {
        return $this->template;
    }

    /**
     * Sets the MJML template.
     *
     * @param string $value
     * @return $this
     */
    public function setTemplate(string $value) : TemplateAsHtmlQuery
    {
        $this->template = $value;

        return $this;
    }

    /**
     * Gets the parameters for the template.
     *
     * @return array
     */
    public function getTemplateParameters() : array
    {
        return $this->templateParameters;
    }

    /**
     * Sets the parameters for the template.
     *
     * @param array $value
     * @return $this
     */
    public function setTemplateParameters(array $value) : TemplateAsHtmlQuery
    {
        $this->templateParameters = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getVariables() : array
    {
        $variables = parent::getVariables();

        $variables['templateEncoded'] = base64_encode($this->getTemplate());
        $variables['templateParametersJsonEncoded'] = base64_encode(json_encode($this->getTemplateParameters()) ?: '');

        return $variables;
    }
}
