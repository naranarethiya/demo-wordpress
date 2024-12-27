<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Events;

use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher_Template;

/**
 * Event for when a new gift certificate template is created.
 */
class GiftCertificateTemplateCreatedEvent extends AbstractGiftCertificateTemplateEvent
{
    /**
     * Constructor.
     *
     * @param WC_Voucher_Template $voucherTemplate
     */
    public function __construct(WC_Voucher_Template $voucherTemplate)
    {
        parent::__construct($voucherTemplate);

        $this->action = 'create';
    }
}
