<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\ListLevelsByRemoteIdOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Level;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\CreateOrUpdateLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadLevelOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\CreateOrUpdateLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListLevelsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadLevelResponseContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

interface LevelsServiceContract
{
    /**
     * Create or update a level.
     *
     * @param CreateOrUpdateLevelOperationContract $operation
     *
     * @return CreateOrUpdateLevelResponseContract
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function createOrUpdateLevel(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract;

    /**
     * Create or update a level for the given product.
     *
     * If a create is attempted but there is already a level upstream, it will be mapped locally and updated instead.
     *
     * @param CreateOrUpdateLevelOperationContract $operation
     *
     * @return CreateOrUpdateLevelResponseContract
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function createOrUpdateLevelWithRepair(CreateOrUpdateLevelOperationContract $operation) : CreateOrUpdateLevelResponseContract;

    /**
     * Read a level.
     *
     * @param ReadLevelOperationContract $operation
     *
     * @return ReadLevelResponseContract
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function readLevel(ReadLevelOperationContract $operation) : ReadLevelResponseContract;

    /**
     * Lists levels based on provided product ids.
     *
     * @param ListLevelsByRemoteIdOperationContract $operation
     *
     * @return ListLevelsResponseContract
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function listLevelsByRemoteProductId(ListLevelsByRemoteIdOperationContract $operation) : ListLevelsResponseContract;

    /**
     * Read a level with ability to repair.
     *
     * @param ReadLevelOperationContract $operation
     *
     * @return ReadLevelResponseContract
     */
    public function readLevelWithRepair(ReadLevelOperationContract $operation) : ReadLevelResponseContract;

    /**
     * Accepts a list of levels and maps the matching level to the given product.
     *
     * @param Level[] $levels
     * @param Product $product
     *
     * @return void
     */
    public function mapLevelToProduct(array $levels, Product $product) : void;
}
