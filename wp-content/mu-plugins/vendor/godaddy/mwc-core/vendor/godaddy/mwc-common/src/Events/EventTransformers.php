<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Exceptions\EventTransformFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ConfigHelper;
use InvalidArgumentException;

/**
 * Event transformers handler.
 */
class EventTransformers
{
    /**
     * Transforms the event based on the defined transformers in `events.transformers` configuration.
     *
     * @param EventContract $event
     */
    public static function transform(EventContract $event)
    {
        try {
            foreach (static::getTransformers($event) as $transformerClass) {
                static::handleTransformer($event, $transformerClass);
            }
        } catch (SentryException $exception) {
            // do nothing - the exception will be automatically reported to Sentry
        } catch (Exception $exception) {
            EventTransformFailedException::getNewInstance($exception->getMessage(), $exception);
        }
    }

    /**
     * Gets a list of transformers for a given event.
     *
     * @param EventContract $event
     * @return string[] array of class names
     * @throws BaseException
     */
    public static function getTransformers(EventContract $event) : array
    {
        return ConfigHelper::getClassNamesUsingClassOrInterfacesAsKeys('events.transformers', $event);
    }

    /**
     * Handles the transformer with the given class name.
     *
     * @param EventContract $event
     * @param string $transformerClass
     * @return void
     */
    protected static function handleTransformer(EventContract $event, string $transformerClass) : void
    {
        try {
            $transformer = static::getTransformer($transformerClass);
            if ($transformer->shouldHandle($event)) {
                $transformer->handle($event);
            }
        } catch (SentryException $exception) {
            // do nothing - the exception will be automatically reported to Sentry
        } catch (Exception $exception) {
            EventTransformFailedException::getNewInstance($exception->getMessage(), $exception);
        }
    }

    /**
     * Gets the transformer by the given class name.
     *
     * @param string $transformerClass
     * @return AbstractEventTransformer
     * @throws InvalidArgumentException
     */
    protected static function getTransformer(string $transformerClass) : AbstractEventTransformer
    {
        try {
            $transformer = ContainerFactory::getInstance()->getSharedContainer()->get($transformerClass);
        } catch(ContainerException $e) {
            // Try new without params - this was the behavior before DI container.
            $transformer = new $transformerClass;
        }

        if (! is_object($transformer) || ! $transformer instanceof AbstractEventTransformer) {
            throw new InvalidArgumentException("{$transformerClass} does not extend AbstractEventTransformer");
        }

        return $transformer;
    }
}
