<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;
use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher_Template;

/**
 * Abstract gift certificate template event class.
 */
abstract class AbstractGiftCertificateTemplateEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var WC_Voucher_Template The WooCommerce voucher template */
    protected $voucherTemplate;

    /**
     * AbstractGiftCertificateTemplateEvent constructor.
     *
     * @param WC_Voucher_Template $voucherTemplate
     */
    public function __construct(WC_Voucher_Template $voucherTemplate)
    {
        $this->voucherTemplate = $voucherTemplate;
        $this->resource = 'gift_certificate_template';
    }

    /**
     * Builds the initial data for the event.
     *
     * @return array
     */
    protected function buildInitialData() : array
    {
        return [
            'template_type'     => $this->voucherTemplate->get_voucher_type(),
            'redeemable_online' => $this->voucherTemplate->is_redeemable_online(),
        ];
    }
}
