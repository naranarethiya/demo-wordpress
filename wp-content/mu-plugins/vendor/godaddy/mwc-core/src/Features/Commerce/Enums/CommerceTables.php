<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums;

use GoDaddy\WordPress\MWC\Core\Traits\EnumTrait;

/**
 * Enum-like class for defining Commerce table names.
 */
class CommerceTables
{
    use EnumTrait;

    public const Contexts = 'godaddy_mwc_commerce_contexts';
    public const ResourceMap = 'godaddy_mwc_commerce_map_ids';
    public const ResourceTypes = 'godaddy_mwc_commerce_map_resource_types';
    public const ResourceUpdates = 'godaddy_mwc_commerce_resource_updates';
    public const SkippedResources = 'godaddy_mwc_commerce_skipped_resources';
    public const WebhookSubscriptions = 'godaddy_mwc_commerce_webhook_subscriptions';
}
