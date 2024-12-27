<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\Events\Transformers;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Events\Transformers\Traits\IsProductEventTransformerTrait;
use GoDaddy\WordPress\MWC\CostOfGoods\WC_COG_Product;
use WC_Product;

/**
 * Product event transformer.
 */
class ProductEventTransformer extends AbstractEventTransformer
{
    use IsProductEventTransformerTrait;

    /**
     * Handles and perhaps modifies the event.
     *
     * @param ModelEvent|EventContract $event the event, perhaps modified by the method
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        $data = $event->getData();

        foreach ($this->getCostData($event) as $key => $value) {
            ArrayHelper::set($data, "resource.{$key}", $value);
        }

        $event->setData($data);
    }

    /**
     * May get the product cost if Cost of Goods is enabled.
     *
     * @param EventContract|ModelEvent $event
     * @return array
     * @throws Exception
     */
    protected function getCostData(EventContract $event) : array
    {
        $productId = ArrayHelper::get($event->getData(), 'resource.id', 0);
        $product = ProductsRepository::get($productId);

        if (! $product || ! class_exists('\GoDaddy\WordPress\MWC\CostOfGoods\WC_COG_Product')) {
            return ['productCost' => $this->convertCostToCurrencyAmountData(0)];
        }

        return $product->is_type('variable')
            ? $this->getCostDataForVariableProduct($product)
            : $this->getCostDataForSimpleProduct($product);
    }

    /**
     * Gets the product cost for a simple product.
     *
     * @param WC_Product|int $product
     * @return array
     */
    protected function getCostDataForSimpleProduct($product) : array
    {
        $cost = WC_COG_Product::get_cost($product);

        return [
            'productCost' => $this->convertCostToCurrencyAmountData((float) $cost),
        ];
    }

    /**
     * Gets the product costs for a variable product.
     *
     * @param WC_Product|int $product
     * @return array
     */
    protected function getCostDataForVariableProduct($product) : array
    {
        list($min, $max) = WC_COG_Product::get_variable_product_min_max_costs($product);

        $average = WC_COG_Product::get_variable_product_average_costs($product);

        return [
            'productCost'    => $this->convertCostToCurrencyAmountData((float) $average),
            'productCostMin' => $this->convertCostToCurrencyAmountData((float) $min),
            'productCostMax' => $this->convertCostToCurrencyAmountData((float) $max),
        ];
    }

    /**
     * Converts a product amount from source.
     *
     * @param float $amount
     * @return array
     */
    protected function convertCostToCurrencyAmountData(float $amount) : array
    {
        return (new CurrencyAmountAdapter($amount, WooCommerceRepository::getCurrency()))
            ->convertFromSource()
            ->toArray();
    }
}
