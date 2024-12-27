<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class GdpSipRecommendationNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = true;

    /** {@inheritdoc} */
    protected $type = self::TYPE_INFO;

    /** {@inheritdoc} */
    protected $id = 'mwc-godaddy-payments-sip-recommendation';

    /**
     * Constructor for GdpSipRecommendationNotice notice.
     */
    public function __construct()
    {
        $this->setButtonUrl(esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=godaddy-payments-payinperson')));
        $this->setButtonText(__('Get Started', 'mwc-core'));
        $this->setContent(__('Use GoDaddy Payments Selling in Person to sync local pickup and delivery orders to your Smart Terminal. Sell anything, anywhere and get paid fast with next-day deposits.', 'mwc-core'));
        $this->setTitle(sprintf(
            '<img src="%1$s" alt="'.esc_attr__('Provided by GoDaddy', 'mwc-core').'"/>
                <h3>'.__('GoDaddy Selling in Person', 'mwc-core').'</h3>',
            esc_url(WordPressRepository::getAssetsUrl('images/branding/gd-icon.svg')),
        ));
        $this->setCssClasses(['mwc-godaddy-payments-recommendation']);
        $this->setCssButtonClasses(['mwc-button']);
    }
}
