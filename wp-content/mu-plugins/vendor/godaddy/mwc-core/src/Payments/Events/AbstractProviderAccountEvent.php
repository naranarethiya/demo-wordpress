<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Abstract Provider Account event class.
 */
abstract class AbstractProviderAccountEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var string */
    protected $providerName;

    /**
     * AbstractProviderAccountEvent constructor.
     *
     * @param string $providerName
     */
    public function __construct(string $providerName)
    {
        $this->providerName = $providerName;
        $this->resource = 'account';
    }

    /**
     * Builds the initial data for the current event.
     *
     * @return array
     */
    protected function buildInitialData() : array
    {
        return [
            'account' => [
                'providerName' => $this->providerName,
            ],
        ];
    }
}
