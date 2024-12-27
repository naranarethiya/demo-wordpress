<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

/**
 * Contract for handlers that can create and update {@see Category} objects.
 */
interface CategoriesGatewayContract extends CanCreateCategoriesContract, CanUpdateCategoriesContract, CanReadCategoriesContract, CanListCategoriesContract
{
}
