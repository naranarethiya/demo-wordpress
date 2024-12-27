<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\StockStatsRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Events\Transformers\Traits\IsProductEventTransformerTrait;

class AddProductSummaryProductEventTransformer extends AbstractEventTransformer
{
    use IsProductEventTransformerTrait;

    /**
     * Modifies product events to add products summary info.
     *
     * @param ModelEvent $event
     * @return void
     */
    public function handle(EventContract $event) : void
    {
        $data = $event->getData();

        ArrayHelper::set($data, 'summary', $this->getProductSummary());

        $event->setData($data);
    }

    /**
     * @return ?array<string, int>
     */
    protected function getProductSummary() : ?array
    {
        $productCount = ArrayHelper::get(StockStatsRepository::getProductCountByStockStatus(), 'products');

        if (is_null($productCount)) {
            return null;
        }

        return [
            'products' => TypeHelper::int($productCount, 0),
        ];
    }
}
