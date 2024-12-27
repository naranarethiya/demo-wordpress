<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Configuration\Contracts\FeatureRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Core\Features\Categories;

class FeatureRuntimeConfiguration implements FeatureRuntimeConfigurationContract
{
    /** @var string Feature's unique name identifier */
    protected string $featureName;

    /**
     * {@inheritDoc}
     */
    public function setFeatureName(string $value)
    {
        $this->featureName = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName() : string
    {
        return TypeHelper::string($this->getConfigurationValue('name'), '');
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription() : string
    {
        return TypeHelper::string($this->getConfigurationValue('description'), '');
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentationUrl() : string
    {
        return TypeHelper::string($this->getConfigurationValue('documentation_url'), '');
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsUrl() : string
    {
        return TypeHelper::string($this->getConfigurationValue('settings_url'), '');
    }

    /**
     * {@inheritDoc}
     */
    public function getCategories() : array
    {
        /** @var array<Categories::*> $categories */
        $categories = $this->getConfigurationValue('categories');

        return $categories;
    }

    /**
     * Gets a configuration property value for the current feature.
     *
     * @param string $property
     * @return mixed
     */
    protected function getConfigurationValue(string $property)
    {
        return Configuration::get("features.{$this->featureName}.{$property}");
    }
}
