<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;

/**
 * Adapts channel data from a GDM API response to a native core Channel object.
 */
class ChannelAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> Channel data from the API response */
    protected $source;

    /**
     * ChannelAdapter constructor.
     *
     * @param array<string, mixed> $data Channel data from the API response.
     */
    public function __construct(array $data)
    {
        $this->source = $data;
    }

    /**
     * Converts channel data to a native Channel object.
     *
     * @return Channel
     */
    public function convertFromSource() : Channel
    {
        return (new Channel())
            ->setProperties(array_filter([
                'id'   => ArrayHelper::get($this->source, 'id', 0),
                'uuid' => ArrayHelper::get($this->source, 'uuid', ''),
                'name' => ArrayHelper::get($this->source, 'name', ''),
                'type' => strtolower(TypeHelper::string(ArrayHelper::get($this->source, 'channel_type_display_name'), '')),
            ]));
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource()
    {
        // Not implemented.
        return [];
    }
}
