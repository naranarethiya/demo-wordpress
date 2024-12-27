<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Notices;

use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class GdpRegisterRecommendationNotice extends Notice
{
    use CanGetNewInstanceTrait;

    /** {@inheritdoc} */
    protected $dismissible = true;

    /** {@inheritdoc} */
    protected $type = self::TYPE_INFO;

    /** {@inheritdoc} */
    protected $id = 'mwc-godaddy-payments-recommendation';

    /**
     * Constructor for GdpRegisterRecommendationNotice notice.
     */
    public function __construct()
    {
        $this->setButtonUrl(esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&gdpsetup=true')));
        $this->setButtonText(__('Get Started', 'mwc-core'));
        $this->setContent(sprintf(
            '<img src="%1$s" alt="'.esc_attr__('Provided by GoDaddy', 'mwc-core').'"/>
                <p>'.__('Sell online and in person with GoDaddy Payments. Sync local pickup and delivery orders right to your Smart Terminal, then get paid fast with next-day deposits.', 'mwc-core').'</p>',
            esc_url(WordPressRepository::getAssetsUrl('images/branding/gd-icon.svg')),
        ));
        $this->setTitle(__('GoDaddy Payments'));
        $this->setCssClasses(['mwc-godaddy-payments-recommendation']);
    }
}
