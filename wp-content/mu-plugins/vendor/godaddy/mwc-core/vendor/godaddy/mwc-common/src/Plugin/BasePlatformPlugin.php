<?php

namespace GoDaddy\WordPress\MWC\Common\Plugin;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Migrations\MigrationHandler;
use GoDaddy\WordPress\MWC\Common\Plugin\Contracts\PlatformPluginContract;
use GoDaddy\WordPress\MWC\Common\Repositories\SentryRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use ReflectionClass;

/**
 * Base platform plugin.
 */
class BasePlatformPlugin implements PlatformPluginContract
{
    use HasComponentsTrait;

    /** @var string[] classes to instantiate */
    protected $classesToInstantiate;

    /** @var array configuration values */
    protected $configurationValues;

    /** @var string[] configuration directories */
    protected $configurationDirectories = ['configurations'];

    /** @var string plugin name */
    protected $name;

    /** @var array list of components to instantiate */
    protected $componentClasses = [];

    /**
     * Initializes the plugin.
     */
    public function load()
    {
        $this->instantiateConfigurationValues();
    }

    /**
     * Performs actions that this contract should do just after configuration is loaded.
     *
     * @return void
     */
    public function onConfigurationLoaded()
    {
        WordPressRepository::requireWordPressInstance();

        // @NOTE: Initialize error reporting -- Must be called after configurations are loaded
        $this->initializeErrorReporting();

        $this->runMigrations();

        // @NOTE: Instantiate required classes
        $this->instantiatePluginClasses();
    }

    /**
     * Runs all migrations.
     *
     * @return void
     */
    protected function runMigrations() : void
    {
        MigrationHandler::getNewInstance()->load();
    }

    /**
     * Initializes Error Reporting and Tracking.
     */
    protected function initializeErrorReporting()
    {
        SentryRepository::initialize();
    }

    /**
     * Gets the classes that should be instantiated when initializing the inheriting plugin.
     *
     * @NOTE This is here so it can be overridden if needed before setting values
     *
     * @return array
     */
    protected function getClassesToInstantiate() : array
    {
        return ArrayHelper::wrap($this->classesToInstantiate);
    }

    /**
     * Gets configuration values.
     *
     * @NOTE This is here so it can be overridden if needed before setting values.
     *
     * @return array
     */
    protected function getConfigurationValues() : array
    {
        return ArrayHelper::wrap($this->configurationValues);
    }

    /**
     * Gets configuration directories.
     *
     * @NOTE This is here so it can be overridden if needed before setting values.
     *
     * @return string[]
     */
    public function getConfigurationDirectories() : array
    {
        return $this->configurationDirectories;
    }

    /**
     * Gets the absolute paths of the configuration directories for this platform plugin.
     *
     * @return string[] List of absolute configuration directory paths.
     */
    public function getAbsolutePathOfConfigurationDirectories() : array
    {
        return $this->getAbsolutePathDirectories($this->getConfigurationDirectories());
    }

    /**
     * Converts the given list of directory paths into absolute paths.
     *
     * The given paths are assumed to be relative to the root directory in the project in which the called class was defined.
     *
     * @return string[]
     */
    protected function getAbsolutePathDirectories(array $directories) : array
    {
        $parentDirectory = StringHelper::trailingSlash($this->getProjectRoot());

        return array_map(static function ($directory) use ($parentDirectory) {
            return StringHelper::trailingSlash($parentDirectory.$directory);
        }, $directories);
    }

    /**
     * Gets the absolute path to the directory that contains the src directory, with a trailing slash.
     *
     * Subclasses can override this method if they need to provide a different path or want to avoid using {@see ReflectionClass}.
     *
     * @return string
     */
    protected function getProjectRoot() : string
    {
        // We can't use __DIR__ because the method would always return the path to the parent of the src directory in mwc-common.
        return StringHelper::before((new ReflectionClass(static::class))->getFileName() ?: '', 'src');
    }

    /**
     * Gets plugin prefix.
     *
     * @return string
     */
    protected function getPluginPrefix() : string
    {
        $pluginName = $this->name ?: StringHelper::afterLast(Configuration::get('wordpress.absolute_path'), '/');

        return strtoupper($pluginName);
    }

    /**
     * Instantiates the plugin constants and configuration values.
     */
    protected function instantiateConfigurationValues()
    {
        foreach ($this->getConfigurationValues() as $key => $value) {
            $this->defineConfigurationConstant($key, $value);
        }
    }

    /**
     * Safely converts the platform's configuration into global constant.
     *
     * @param string $configurationName
     * @param string $configurationValue
     */
    protected function defineConfigurationConstant(string $configurationName, string $configurationValue) : void
    {
        $pluginPrefix = $this->getPluginPrefix();

        // the `strtolower()` call here before conversion to snake_case is significant - it prevents MWC-CORE from becoming M_W_C_C_O_R_E
        $constantName = strtoupper(StringHelper::snakeCase(strtolower("{$pluginPrefix} {$configurationName}")));

        if (! defined($constantName)) {
            define($constantName, $configurationValue);
        }
    }

    /**
     * Instantiates the plugin specific classes.
     *
     * @throws Exception
     */
    protected function instantiatePluginClasses()
    {
        foreach ($this->getClassesToInstantiate() as $class => $mode) {
            if (is_bool($mode) && $mode) {
                $this->maybeInstantiateClass($class);
            }

            if ($mode === 'cli' && WordPressRepository::isCliMode()) {
                $this->maybeInstantiateClass($class);
            }

            if ($mode === 'web' && ! WordPressRepository::isCliMode()) {
                $this->maybeInstantiateClass($class);
            }
        }

        $this->loadComponents();
    }

    /**
     * Instantiates a class.
     *
     * Performs a check whether the class contains a conditional feature if {@see IsConditionalFeatureTrait} is available for that class.
     * If so, runs the trait method {@see IsConditionalFeatureTrait::shouldLoadConditionalFeature()} to determine whether the class should be loaded.
     *
     * @param string $class class name
     * @return object|null
     */
    protected function maybeInstantiateClass(string $class)
    {
        $conditionalLoadMethod = '::shouldLoadConditionalFeature';

        if (is_callable($class.$conditionalLoadMethod)
            && ArrayHelper::contains(ArrayHelper::wrap(class_uses($class, false)), IsConditionalFeatureTrait::class)
        ) {
            if (call_user_func($class.$conditionalLoadMethod)) {
                return $this->instantiateClass($class);
            }
        } else {
            return $this->instantiateClass($class);
        }

        return null;
    }

    /**
     * Instantiates a class using the dependency injection container.
     *
     * @param string $classOrAbstractName
     * @return object|null
     */
    protected function instantiateClass(string $classOrAbstractName) : ?object
    {
        try {
            $instance = ContainerFactory::getInstance()->getSharedContainer()->get($classOrAbstractName);

            return is_object($instance) ? $instance : null;
        } catch (ContainerException $e) {
            return null;
        }
    }
}
