<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;

class AddBankInfoNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = false;

    /** {@inheritdoc} */
    protected $type = self::TYPE_SUCCESS;

    /** {@inheritdoc} */
    protected $id = 'mwc-payments-godaddy-payments-complete-profile';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->setButtonUrl(add_query_arg([
            'businessId' => Poynt::getBusinessId(),
            'storeId'    => Poynt::getSiteStoreId(),
        ], Poynt::getHubUrl()));

        $this->setButtonText(__('Link bank account', 'mwc-core'));
        $this->setContent(__("You've got money waiting with GoDaddy Payments! Add your banking info to get your payouts deposited.", 'mwc-core'));
    }
}
