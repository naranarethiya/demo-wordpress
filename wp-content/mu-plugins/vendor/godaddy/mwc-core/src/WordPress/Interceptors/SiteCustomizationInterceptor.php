<?php

namespace GoDaddy\WordPress\MWC\Core\WordPress\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Events\Enums\EventBridgeEventActionEnum;
use GoDaddy\WordPress\MWC\Core\Events\Site\AbstractSiteOptionEvent;
use GoDaddy\WordPress\MWC\Core\Events\Traits\CanDetermineEventActionTrait;

/**
 * An interceptor to hook into site options changes.
 */
class SiteCustomizationInterceptor extends AbstractInterceptor
{
    use CanDetermineEventActionTrait;

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        foreach (array_keys($this->getOptionsEventClasses()) as $optionName) {
            Register::action()
                ->setGroup('add_option_'.$optionName)
                ->setHandler([$this, 'onOptionAdded'])
                ->setPriority(PHP_INT_MIN)
                ->setArgumentsCount(1)
                ->execute();

            Register::action()
                ->setGroup('update_option_'.$optionName)
                ->setHandler([$this, 'onSiteOptionUpdated'])
                ->setPriority(PHP_INT_MIN)
                ->setArgumentsCount(3)
                ->execute();

            Register::action()
                ->setGroup('delete_option_'.$optionName)
                ->setHandler([$this, 'onOptionDeleted'])
                ->setPriority(PHP_INT_MIN)
                ->setArgumentsCount(1)
                ->execute();
        }
    }

    /**
     * Handler for the {@see add_option_<option>} action.
     *
     * @param mixed $optionName
     */
    public function onOptionAdded($optionName) : void
    {
        Events::broadcast($this->createEventsForOption(TypeHelper::string($optionName, ''), EventBridgeEventActionEnum::Create));
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param mixed $optionName
     * @return void
     */
    public function onSiteOptionUpdated($oldValue, $newValue, $optionName) : void
    {
        $eventAction = $this->determineEventAction($oldValue, $newValue);

        foreach ($this->createEventsForOption((string) StringHelper::ensureScalar($optionName), $eventAction) as $event) {
            Events::broadcast($event);
        }
    }

    /**
     * Handler for the {@see delete_option_<option>} action.
     *
     * @param mixed $optionName
     */
    public function onOptionDeleted($optionName) : void
    {
        Events::broadcast($this->createEventsForOption(TypeHelper::string($optionName, ''), EventBridgeEventActionEnum::Delete));
    }

    /**
     * Creates event instances for the given option.
     *
     * @param string $optionName
     * @param EventBridgeEventActionEnum::* $eventAction
     *
     * @return AbstractSiteOptionEvent[]
     */
    protected function createEventsForOption(string $optionName, string $eventAction) : array
    {
        if (! $eventClasses = $this->getOptionEventClasses($optionName)) {
            return [];
        }

        return array_map(static fn ($eventClass) => new $eventClass($eventAction), $eventClasses);
    }

    /**
     * @return class-string<AbstractSiteOptionEvent>[]|null
     */
    protected function getOptionEventClasses(string $optionName) : ?array
    {
        return $this->getOptionsEventClasses()[$optionName] ?? null;
    }

    /**
     * @return array<string, class-string<AbstractSiteOptionEvent>[]>
     */
    protected function getOptionsEventClasses() : array
    {
        $options = array_map(
            static fn ($events) => TypeHelper::arrayOfClassStrings(ArrayHelper::wrap($events), AbstractSiteOptionEvent::class),
            ArrayHelper::wrap(Configuration::get('wordpress.monitoredItems.options', []))
        );

        return array_filter($options);
    }
}
