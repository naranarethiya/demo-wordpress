<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;
use GoDaddy\WordPress\MWC\Core\Events\Enums\EventBridgeEventActionEnum;

/**
 * @method static static getNewInstance(string $postType, string $postStatus, string $action)
 */
class PostTypeEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;
    use CanGetNewInstanceTrait;

    /**
     * @var string The status for this post, such as publish, or draft.
     */
    protected string $status;

    /**
     * Constructor.
     *
     * @param string                        $postType
     * @param string                        $postStatus The post status this post was set to when updated.
     * @param EventBridgeEventActionEnum::* $action
     */
    public function __construct(string $postType, string $postStatus, string $action)
    {
        $this->resource = $postType;
        $this->status = $postStatus;
        $this->action = $action;
    }

    /**
     * {@inheritDoc}
     * @return array<string,mixed>
     */
    protected function buildInitialData() : array
    {
        return [
            'resource' => [
                'status' => $this->status,
            ],
        ];
    }
}
