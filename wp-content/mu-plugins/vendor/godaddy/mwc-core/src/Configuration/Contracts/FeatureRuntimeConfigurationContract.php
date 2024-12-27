<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Categories;

interface FeatureRuntimeConfigurationContract
{
    /**
     * Sets the feature name identifier.
     *
     * @param string $value
     * @return $this
     */
    public function setFeatureName(string $value);

    /**
     * Gets the feature display name.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Gets the feature description.
     *
     * @return string
     */
    public function getDescription() : string;

    /**
     * Gets the feature documentation URL.
     *
     * @return string
     */
    public function getDocumentationUrl() : string;

    /**
     * Gets the feature settings URL.
     *
     * @return string
     */
    public function getSettingsUrl() : string;

    /**
     * Gets the feature categories.
     *
     * @return array<Categories::*>
     */
    public function getCategories() : array;
}
