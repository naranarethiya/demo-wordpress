<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasCommerceCapabilitiesTrait;

/**
 * Abstract base integration class for Commerce integrations.
 */
abstract class AbstractIntegration extends AbstractFeature
{
    use HasComponentsFromContainerTrait;
    use HasCommerceCapabilitiesTrait;

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'commerce.integrations.'.static::getIntegrationName();
    }

    /**
     * Gets the name of the configuration for this integration.
     *
     * @return string
     */
    abstract protected static function getIntegrationName() : string;

    /**
     * Initializes the component.
     *
     * @throws Exception
     */
    public function load() : void
    {
        $this->loadComponents();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, bool>
     */
    public static function getCommerceCapabilities() : array
    {
        /** @var array<string, bool> $integrationCapabilities */
        $integrationCapabilities = TypeHelper::array(static::getConfiguration('capabilities', []), []);

        // apply any globally disabled capabilities to the integration's capabilities
        array_walk($integrationCapabilities, function (&$value, $capability, $globalCapabilities) {
            $value = $value && ArrayHelper::get($globalCapabilities, $capability, $value);
        }, Commerce::getCommerceCapabilities());

        return $integrationCapabilities;
    }

    /**
     * Enable a capability by setting its config to true.
     *
     * @param string $capability
     * @return void
     */
    public static function enableCapability(string $capability) : void
    {
        Configuration::set(sprintf('features.%s.capabilities.%s', static::getName(), $capability), true);
    }

    /**
     * Disable a capability by setting its config to false.
     *
     * @param string $capability
     * @return void
     */
    public static function disableCapability(string $capability) : void
    {
        Configuration::set(sprintf('features.%s.capabilities.%s', static::getName(), $capability), false);
    }

    /**
     * Temporarily disable writes while executing the supplied callable.
     *
     * If an exception is thrown during the callable's execution, writes will
     * be re-enabled before the exception is re-thrown.
     *
     * @param callable $callable
     *
     * @return mixed
     */
    public static function withoutWrites(callable $callable)
    {
        return static::executeCallableWithoutCapability(Commerce::CAPABILITY_WRITE, $callable);
    }

    /**
     * Temporarily disable reads while executing the supplied callable.
     *
     * If an exception is thrown during the callable's execution, reads will
     * be re-enabled before the exception is re-thrown.
     *
     * @param callable $callable
     *
     * @return mixed|void
     */
    public static function withoutReads(callable $callable)
    {
        return static::executeCallableWithoutCapability(Commerce::CAPABILITY_READ, $callable);
    }

    /**
     * Temporarily disable broadcasting integration-related events while executing the supplied callable.
     *
     * Note: This does not disable events not dispatched directly by the integration.
     *
     * If an exception is thrown during the callable's execution, firing events will
     * be re-disabled before the exception is re-thrown.
     *
     * @param callable $callable
     *
     * @return mixed|void
     */
    public static function withoutEvents(callable $callable)
    {
        return static::executeCallableWithoutCapability(Commerce::CAPABILITY_EVENTS, $callable);
    }

    /**
     * Temporarily disables a capability while executing the supplied callable.
     *
     * If an exception is thrown during the callable's execution, the capability
     * will be re-enabled before the exception is re-thrown.
     *
     * @param string   $capability
     * @param callable $callable
     *
     * @return mixed|void
     */
    protected static function executeCallableWithoutCapability(string $capability, callable $callable)
    {
        $capabilityEnabled = static::hasCommerceCapability($capability);
        if ($capabilityEnabled) {
            static::disableCapability($capability);
        }

        try {
            $response = $callable();
        } finally {
            if ($capabilityEnabled) {
                static::enableCapability($capability);
            }
        }

        if (isset($response)) {
            return $response;
        }
    }
}
