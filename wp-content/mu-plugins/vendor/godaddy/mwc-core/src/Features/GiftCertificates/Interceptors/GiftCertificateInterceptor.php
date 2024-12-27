<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Features\EnabledFeaturesCache;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Adapters\GiftCertificateAdapter;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\GiftCertificates;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Models\GiftCertificate;
use GoDaddy\WordPress\MWC\Core\WordPress\NewPostObjectFlag;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher;
use WP_Post;

/**
 * An interceptor to hook on gift certificate actions and filters.
 */
class GiftCertificateInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
            ->setGroup('wp_insert_post')
            ->setHandler([$this, 'onWpInsertPost'])
            ->setArgumentsCount(3)
            ->execute();
    }

    /**
     * Handles the insert post hook, which is triggered whenever a voucher is updated.
     *
     * @internal
     *
     * @param int $postId
     * @param WP_Post $post
     * @param bool $isUpdate
     * @throws Exception
     */
    public function onWpInsertPost($postId, $post, $isUpdate)
    {
        if ($voucher = $this->getVoucher($post)) {
            $giftCertificate = $this->getConvertedGiftCertificate($voucher);

            if ('trash' === $giftCertificate->getStatus()) {
                $giftCertificate->delete();
            } elseif (WordPressRepository::isAdmin()) {
                $this->saveOrUpdateFromAdmin($giftCertificate);
            } elseif (WooCommerceRepository::isCheckoutPage()) {
                $this->saveOrUpdateFromCheckout($giftCertificate, $isUpdate);
            } elseif ($isUpdate) {
                $giftCertificate->update();
            } else {
                $giftCertificate->save();
            }
        }
    }

    /**
     * Converts a WC_Voucher object into a native gift certificate object.
     *
     * @param WC_Voucher $voucher
     * @return GiftCertificate
     * @throws Exception
     */
    protected function getConvertedGiftCertificate(WC_Voucher $voucher) : GiftCertificate
    {
        return (new GiftCertificateAdapter($voucher))->convertFromSource();
    }

    /**
     * Gets a voucher from a WP_Post.
     *
     * @param WP_Post $post
     * @return false|WC_Voucher
     */
    protected function getVoucher($post)
    {
        return null !== $post && 'wc_voucher' === $post->post_type
            ? wc_pdf_product_vouchers()->get_voucher_handler_instance()->get_voucher($post->ID)
            : false;
    }

    /**
     * Saves or updates a gift certificate by an admin page.
     *
     * This is necessary as gift certificates created by its admin page have an intermediary
     * draft status that was generating invalid create events.
     *
     * @param GiftCertificate $giftCertificate
     */
    protected function saveOrUpdateFromAdmin(GiftCertificate $giftCertificate) : void
    {
        $flag = NewPostObjectFlag::getNewInstance($giftCertificate->getId());

        if ($giftCertificate->isDraft()) {
            $flag->turnOn();

            return;
        }

        if ($flag->isOn()) {
            $giftCertificate->save();
            $flag->turnOff();

            return;
        }

        $giftCertificate->update();
    }

    /**
     * Saves or updates a gift certificate from the checkout page.
     *
     * @param GiftCertificate $giftCertificate
     * @param bool $isUpdate
     */
    protected function saveOrUpdateFromCheckout(GiftCertificate $giftCertificate, bool $isUpdate)
    {
        // this will prevent a GC save() call with just a few properties set
        if (! $isUpdate) {
            return;
        }

        if ($giftCertificate->isRedeemed()) {
            $giftCertificate->update();

            return;
        }

        $giftCertificate->save();
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return
            parent::shouldLoad() &&
            ArrayHelper::contains(EnabledFeaturesCache::getNewInstance()->get() ?? [], GiftCertificates::getName());
    }
}
