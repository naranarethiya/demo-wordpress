<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Events\PageViewEvent;

/**
 * Producer for events in admin pages.
 */
class PageEventsProducer implements ProducerContract
{
    /**
     * Sets up the page events producer.
     *
     * @deprecated
     *
     * @throws Exception
     */
    public function setup()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '2.18.1', __CLASS__.'::load');

        $this->load();
    }

    /**
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('current_screen')
            ->setHandler([$this, 'firePageViewEvent'])
            ->execute();
    }

    /**
     * Fires page view event.
     *
     * @internal
     *
     * @throws Exception
     */
    public function firePageViewEvent()
    {
        // bail early if this is not an admin screen
        if (! $currentScreen = WordPressRepository::getCurrentScreen()) {
            return;
        }

        // if no special events fired, then try to fire a generic page view event
        Events::broadcast(new PageViewEvent($currentScreen));
    }
}
