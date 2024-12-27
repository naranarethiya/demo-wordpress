<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Exceptions\SummaryNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\ListSummariesInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ListSummariesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadSummaryOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListSummariesOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ListSummariesResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\Contracts\ReadSummaryResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ListSummariesResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Responses\ReadSummaryResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;

class SummariesService implements SummariesServiceContract
{
    protected bool $useCache = true;

    /** @var CommerceContextContract */
    protected CommerceContextContract $commerceContext;

    /** @var InventoryProviderContract the inventory provider instance */
    protected InventoryProviderContract $provider;

    protected SummariesCachingService $summariesCachingService;

    /** @var ProductMapRepository the product map repository instance */
    protected ProductMapRepository $productMapRepository;

    public function __construct(
        CommerceContextContract $commerceContext,
        InventoryProviderContract $provider,
        SummariesCachingService $summariesByProductIdCachingService,
        ProductMapRepository $productMapRepository
    ) {
        $this->commerceContext = $commerceContext;
        $this->provider = $provider;
        $this->summariesCachingService = $summariesByProductIdCachingService;
        $this->productMapRepository = $productMapRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function readSummary(ReadSummaryOperationContract $operation) : ReadSummaryResponseContract
    {
        $productId = $operation->getProductId();

        if (! $existingRemoteProductId = $this->productMapRepository->getRemoteId($productId)) {
            throw new MissingProductRemoteIdException('Could not get the remote product ID for given product '.$productId);
        }

        $summaries = $this->list(ListSummariesOperation::seed([
            'productIds' => [$existingRemoteProductId],
        ]))->getSummaries();

        $summary = current($summaries);

        if (! $summary instanceof Summary) {
            throw new SummaryNotFoundException('Could not find a remote inventory summary for product '.$existingRemoteProductId);
        }

        return ReadSummaryResponse::getNewInstance($summary);
    }

    /**
     * @param string[] $productIds
     *
     * @return ListSummariesInput
     */
    protected function getListSummariesInput(array $productIds) : ListSummariesInput
    {
        return ListSummariesInput::getNewInstance([
            'storeId'    => $this->commerceContext->getStoreId(),
            'productIds' => $productIds,
        ]);
    }

    /**
     * Returns a copy of given productIds array, after filtering out productIds found in $cachedSummaries.
     *
     * @param string[] $productIds
     * @param Summary[] $cachedSummaries
     *
     * @return string[]
     */
    protected function getUncachedSummariesProductIds(array $productIds, array $cachedSummaries) : array
    {
        return array_values(array_diff(
            $productIds,
            $this->summariesCachingService->getSkippedResourceIds(),
            array_map(static fn ($summary) => $summary->productId, $cachedSummaries)
        ));
    }

    /**
     * List summaries, optionally by productId.
     *
     * @param ListSummariesOperationContract $operation
     *
     * @return ListSummariesResponseContract
     * @throws Exception|CommerceExceptionContract
     */
    public function list(ListSummariesOperationContract $operation) : ListSummariesResponseContract
    {
        $productIds = $operation->getProductIds();

        if ($productIds && $this->useCache) {
            $summaries = $this->listSummariesWithCache($operation);
        } else {
            $summaries = $this->listSummariesFromRemoteService($productIds);
        }

        return ListSummariesResponse::getNewInstance($summaries);
    }

    /**
     * Lists summaries from the gateway.
     *
     * @param string[] $productIds
     *
     * @return Summary[]
     * @throws Exception|CommerceExceptionContract
     */
    protected function listSummariesFromRemoteService(array $productIds) : array
    {
        $listSummariesInput = $this->getListSummariesInput($productIds);

        $gateway = $this->provider->summaries();

        $foundSummaries = $gateway->list($listSummariesInput);

        // mark any productIds that didn't return summary data as skipped for future queries
        $this->summariesCachingService->addSkippedResourceIds($this->getUncachedSummariesProductIds($productIds, $foundSummaries));

        return $foundSummaries;
    }

    /**
     * @param ListSummariesOperationContract $operation
     *
     * @return Summary[]
     *
     * @throws Exception|CommerceExceptionContract
     */
    protected function listSummariesWithCache(ListSummariesOperationContract $operation) : array
    {
        $productIds = $operation->getProductIds();

        $cachedSummaries = $this->summariesCachingService->getMany($productIds);

        $uncachedSummariesProductIds = $this->getUncachedSummariesProductIds($productIds, $cachedSummaries);

        if (! $uncachedSummariesProductIds) {
            return $cachedSummaries;
        }

        $summariesFromRemoteService = $this->listSummariesFromRemoteService($uncachedSummariesProductIds);

        $this->summariesCachingService->setMany($summariesFromRemoteService);

        return array_merge($cachedSummaries, $summariesFromRemoteService);
    }

    /**
     * {@inheritDoc}
     */
    public function withoutCache() : SummariesServiceContract
    {
        $this->useCache = false;

        return $this;
    }
}
