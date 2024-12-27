<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Enums;

use GoDaddy\WordPress\MWC\Common\Traits\EnumTrait;

/**
 * Enum-like class for defining Commerce webhook event types.
 */
class CommerceWebhookEventTypes
{
    use EnumTrait;

    public const CategoryCreated = 'commerce.category.created';
    public const CategoryDeleted = 'commerce.category.deleted';
    public const CategoryUpdated = 'commerce.category.updated';
    public const ProductCreated = 'commerce.product.created';
    public const ProductDeleted = 'commerce.product.deleted';
    public const ProductUpdated = 'commerce.product.updated';
}
