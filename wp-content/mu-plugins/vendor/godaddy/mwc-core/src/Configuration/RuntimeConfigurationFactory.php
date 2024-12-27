<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Contracts\ContainerContract;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Core\Configuration\Contracts\FeatureRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Configuration\Contracts\CartRecoveryEmailsFeatureRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryException;
use RuntimeException;

class RuntimeConfigurationFactory
{
    use IsSingletonTrait;

    /**
     * Gets the desired feature's runtime configuration.
     *
     * @param string $featureName
     * @return FeatureRuntimeConfigurationContract
     * @throws ContainerException|RuntimeException
     */
    public function getFeatureRuntimeConfiguration(string $featureName) : FeatureRuntimeConfigurationContract
    {
        if (! $className = TypeHelper::string(Configuration::get("features.{$featureName}.runtime_configuration"), '')) {
            return $this->createGenericFeatureRuntimeConfiguration($featureName);
        }

        if (! class_exists($className)) {
            throw new RuntimeException("Class {$className} does not exist.");
        }

        $classInterfaces = class_implements($className);

        if (! is_array($classInterfaces) ||
            ! in_array(FeatureRuntimeConfigurationContract::class, $classInterfaces, true)) {
            throw new RuntimeException("{$className} must implement FeatureRuntimeConfigurationContract.");
        }

        /** @var FeatureRuntimeConfigurationContract $configuration */
        $configuration = $this->getDiContainer()->get($className);

        return $configuration;
    }

    /**
     * Creates a generic feature runtime configuration with the given feature name.
     *
     * @param string $featureName
     *
     * @return FeatureRuntimeConfigurationContract
     *
     * @throws ContainerException|EntryNotFoundException
     */
    protected function createGenericFeatureRuntimeConfiguration(string $featureName) : FeatureRuntimeConfigurationContract
    {
        /** @var FeatureRuntimeConfiguration $genericFeatureRuntimeConfiguration */
        $genericFeatureRuntimeConfiguration = $this->getDiContainer()->get(FeatureRuntimeConfiguration::class);

        return $genericFeatureRuntimeConfiguration->setFeatureName($featureName);
    }

    /**
     * Gets the Dependency Injection shared container.
     *
     * @return ContainerContract
     */
    protected function getDiContainer() : ContainerContract
    {
        return ContainerFactory::getInstance()->getSharedContainer();
    }

    /**
     * Returns an instance of Cart Recovery Emails feature runtime configuration.
     *
     * @return CartRecoveryEmailsFeatureRuntimeConfigurationContract
     *
     * @throws CartRecoveryException|ContainerException|RuntimeException
     */
    public function getCartRecoveryEmailsRuntimeConfiguration() : CartRecoveryEmailsFeatureRuntimeConfigurationContract
    {
        $configuration = $this->getFeatureRuntimeConfiguration('cart_recovery_emails');

        if (! $configuration instanceof CartRecoveryEmailsFeatureRuntimeConfigurationContract) {
            throw new CartRecoveryException('Configuration must implement CartRecoveryEmailsFeatureRuntimeConfigurationContract.');
        }

        return $configuration;
    }
}
