<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\TemplatesRepository;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Abstract site event class.
 */
abstract class AbstractSiteEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->resource = 'site';
    }

    /**
     * Builds the initial data for the current event.
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    protected function buildInitialData() : array
    {
        return [
            'site' => [
                'email_template_overrides' => TemplatesRepository::getEmailTemplateOverrides(),
            ],
        ];
    }
}
