<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use Sentry\Event as SentryEvent;

class SentrySampleRateRepository
{
    use IsSingletonTrait;

    /** @var array<class-string, (float|int)>|null */
    protected ?array $sampleRateOverrides = null;

    /**
     * Can the given event be sampled?
     * Sample rate logic copied from {@link https://github.com/getsentry/sentry-php/blob/3.21.0/src/Client.php#L278}.
     *
     * @param SentryEvent $event
     *
     * @return bool
     */
    public function canSampleEvent(SentryEvent $event) : bool
    {
        $sampleRate = $this->getSampleRate($event);

        return ! ($sampleRate < 1 && mt_rand(1, 100) / 100.0 > $sampleRate);
    }

    protected function getSampleRate(SentryEvent $event) : float
    {
        return $this->getSampleRateForEvent($event) ??
            TypeHelper::float(Configuration::get('reporting.sentry.sampleRateParams.defaultRate'), 0.2);
    }

    /**
     * Try to get a sample rate specific to first exception in the event matching a configured sample rate override.
     *
     * Note that one of exception types in the event exception bag has to match an exact class-string in the overrides
     * configuration. Child exceptions won't match the config.
     *
     * @param SentryEvent $event
     *
     * @return float|null
     */
    protected function getSampleRateForEvent(SentryEvent $event) : ?float
    {
        $sampleRateOverrides = static::getSampleRateOverrides();

        if (! $sampleRateOverrides) {
            return null;
        }

        foreach (ArrayHelper::wrap($event->getExceptions()) as $exceptionBag) {
            $sampleRate = $sampleRateOverrides[$exceptionBag->getType()] ?? null;
            if ($sampleRate !== null) {
                return (float) $sampleRate;
            }
        }

        return null;
    }

    /**
     * Get a sample rate specific to first exception in the event matching a configured sample rate override.
     *
     * @return array<class-string, float|int>
     */
    protected function getSampleRateOverrides() : array
    {
        return $this->sampleRateOverrides ??= array_filter(
            TypeHelper::array(Configuration::get('reporting.sentry.sampleRateParams.overrides'), []),
            function ($sampleRate, $className) : bool {
                return (is_float($sampleRate) || is_int($sampleRate)) && is_string($className) && class_exists($className);
            }, ARRAY_FILTER_USE_BOTH
        );
    }
}
