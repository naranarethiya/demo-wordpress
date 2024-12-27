<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Events;

use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher_Template;

/**
 * Event for when a gift certificate template is updated.
 */
class GiftCertificateTemplateUpdatedEvent extends AbstractGiftCertificateTemplateEvent
{
    /**
     * Constructor.
     *
     * @param WC_Voucher_Template $voucherTemplate
     */
    public function __construct(WC_Voucher_Template $voucherTemplate)
    {
        parent::__construct($voucherTemplate);

        $this->action = 'update';
    }
}
