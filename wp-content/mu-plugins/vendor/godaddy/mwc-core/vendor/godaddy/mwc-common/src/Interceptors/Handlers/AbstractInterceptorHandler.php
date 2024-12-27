<?php

namespace GoDaddy\WordPress\MWC\Common\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use Throwable;

abstract class AbstractInterceptorHandler
{
    /**
     * @var int zero-index for which of the args passed to handle() to return in case of error getting an instance.
     */
    protected static int $onFailureReturnArg = 0;

    /**
     * @param mixed ...$args to be passed through to instance method run()
     *
     * @return mixed|void
     */
    public static function handle(...$args)
    {
        if ($instance = static::tryGetInstanceFromContainer()) {
            return $instance->run(...$args);
        }

        return ArrayHelper::get($args, (string) static::$onFailureReturnArg);
    }

    /**
     * Use this to get an instance, catching any errors that happen in instantiation.
     *
     * @return static|null null if there was an error on instantiation
     */
    protected static function tryGetInstanceFromContainer() : ?AbstractInterceptorHandler
    {
        try {
            return ContainerFactory::getInstance()->getSharedContainer()->get(static::class);
        } catch (Throwable $exception) {
            SentryException::getNewInstance('Could not instantiate '.static::class, $exception);

            return null;
        }
    }

    /**
     * The static handle() method calls this to run the handler.
     *
     * @param mixed ...$args
     *
     * @return mixed|void
     */
    abstract public function run(...$args);
}
