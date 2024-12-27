<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Product\ProductAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Models\GiftCertificate;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use WC_Customer;

/**
 * Gift Certificate adapter.
 *
 * Converts between a native core gift certificate object and a voucher object.
 */
class GiftCertificateAdapter implements DataSourceAdapterContract
{
    /** @var WC_Voucher voucher object */
    protected $source;

    /**
     * Adapter constructor.
     */
    public function __construct(WC_Voucher $giftCertificate)
    {
        $this->source = $giftCertificate;
    }

    /**
     * Converts the GiftCertificate from source WC_Voucher.
     *
     * @return GiftCertificate
     * @throws Exception
     */
    public function convertFromSource() : GiftCertificate
    {
        $giftCertificate = new GiftCertificate();

        $this->convertCustomer($giftCertificate);
        $this->convertOrder($giftCertificate);
        $this->convertProduct($giftCertificate);

        return $giftCertificate
            // number and status are not meant to be null, but due to the nature of non typed returns in old code, a sanity check is advised
            ->setNumber($this->source->get_voucher_number() ?? '')
            ->setStatus($this->source->get_status() ?? '')
            ->setId($this->source->get_id())
            ->setTemplateId($this->source->get_template_id());
    }

    /**
     * Converts a native gift certificate object into a WC_Voucher object.
     *
     * @TODO: Implement this method on Gift Certificate V2 {acastro1 2021-12-28}
     *        This method won't be used until V2, and WC_Voucher is likely to be refactored.
     *
     * @return WC_Voucher voucher object
     * @throws Exception
     */
    public function convertToSource() : WC_Voucher
    {
        throw new BaseException('Unsupported operation');
    }

    /**
     * Converts the WC_Customer instance in the source object to a native Customer object.
     *
     * @param GiftCertificate $giftCertificate
     *
     * @throws Exception
     */
    protected function convertCustomer(GiftCertificate $giftCertificate)
    {
        $customerId = $this->source->get_customer_id();

        $wcCustomer = $customerId ? $this->getCustomer($customerId) : null;

        $giftCertificate->setCustomer($wcCustomer ? CustomerAdapter::getNewInstance($wcCustomer)->convertFromSource() : null);
    }

    /**
     * Gets a customer by ID.
     *
     * @param int $customerId
     *
     * @return WC_Customer
     *
     * @throws Exception
     */
    protected function getCustomer(int $customerId) : WC_Customer
    {
        return new WC_Customer($customerId);
    }

    /**
     * Converts the WC_Order instance in the source object to a native Order object.
     *
     * @param GiftCertificate $giftCertificate
     *
     * @throws Exception
     */
    protected function convertOrder(GiftCertificate $giftCertificate)
    {
        $orderId = $this->source->get_order_id();

        $wcOrder = $orderId ? wc_get_order($orderId) : null;

        $giftCertificate->setOrder($wcOrder ? (new OrderAdapter($wcOrder))->convertFromSource() : null);
    }

    /**
     * Converts the WC_Product instance in the source object to a native Product object.
     *
     * @param GiftCertificate $giftCertificate
     *
     * @throws Exception
     */
    protected function convertProduct(GiftCertificate $giftCertificate)
    {
        $productId = $this->source->get_product_id();

        $wcProduct = $productId ? wc_get_product($productId) : null;

        $giftCertificate->setProduct($wcProduct ? (new ProductAdapter($wcProduct))->convertFromSource() : null);
    }
}
