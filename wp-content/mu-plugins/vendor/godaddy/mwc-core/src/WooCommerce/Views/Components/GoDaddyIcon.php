<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components;

use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Helps components displaying a GoDaddy logo as an icon from a SVG image.
 *
 * @TODO this may need some refactoring and better organization, along with {@see GoDaddyBranding} ref MWC-7914 {unfulvio 2022-08-31}
 */
class GoDaddyIcon
{
    use CanGetNewInstanceTrait;

    /**
     * Renders the logo.
     *
     * @return void
     */
    public function render() : void
    {
        ?>
       <img class="mwc-gd-icon" src="<?php echo esc_url(WordPressRepository::getAssetsUrl('images/branding/gd-icon.svg')); ?>" alt="<?php esc_attr_e('GoDaddy', 'mwc-core'); ?>" />
       <?php
    }
}
