<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\WooCommerce\EmailCatcher;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use WC_Email;
use WC_Email_Customer_Note;
use WC_Order;

/**
 * Intercepts WooCommerce emails to adjust handling for Marketplaces-related order emails.
 */
class EmailInterceptor extends AbstractInterceptor
{
    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        /* @see EmailCatcher::addHooks() overridden here via PHP_INT_MAX */
        Register::filter()
            ->setGroup('woocommerce_mail_callback')
            ->setHandler([$this, 'preventSendingMarketplacesOrderCustomerEmails'])
            ->setArgumentsCount(2)
            ->setPriority(PHP_INT_MAX)
            ->execute();

        // the PHP_INT_MIN here guarantees that the marketplaces information will be output before the order items table
        Register::action()
            ->setGroup('woocommerce_email_order_details')
            ->setHandler([$this, 'maybeAddMarketplaceOrderDetails'])
            ->setArgumentsCount(4)
            ->setPriority(PHP_INT_MIN)
            ->execute();
    }

    /**
     * Determines whether the email should be processed by WooCommerce using the default callback.
     *
     * @param WC_Email|mixed $email
     * @return bool
     */
    protected function shouldUseDefaultEmailCallback($email) : bool
    {
        return ! $email instanceof WC_Email
            || $email instanceof WC_Email_Customer_Note
            || ! $email->object instanceof WC_Order
            || ! $email->is_customer_email()
            || $email->is_manual();
    }

    /**
     * Prevents sending emails for WooCommerce orders related to Marketplaces that are customer-facing.
     *
     * @NOTE: we still allow customer-emails that are manually sent (typically from order actions in the order edit screen)
     *
     * @see WC_Email::send() filters the email callback so that the email isn't sent if the conditions are met
     * @see \__return_false()
     *
     * @internal
     *
     * @param string|array|callable|mixed $callback
     * @param WC_Email|mixed $email
     * @return string|array|callable|mixed
     */
    public function preventSendingMarketplacesOrderCustomerEmails($callback, $email)
    {
        if ($this->shouldUseDefaultEmailCallback($email)) {
            return $callback;
        }

        try {
            $order = OrderAdapter::getNewInstance($email->object)->convertFromSource();

            if (! $order->hasMarketplacesChannel()) {
                return $callback;
            }
        } catch (Exception $exception) {
            // since we are in a callback context, we should catch any exceptions and just report them to Sentry
            new SentryException($exception->getMessage(), $exception);

            return $callback;
        }

        return '__return_false';
    }

    /**
     * Maybe adds Marketplaces information to order details in admin emails.
     *
     * Manually sent customer-facing emails can also include these details.
     *
     * @internal
     *
     * @param WC_Order|mixed $order
     * @param bool|mixed $isSentToAdmin
     * @param bool|mixed $isPlainText
     * @param WC_Email|mixed $email
     * @return void
     */
    public function maybeAddMarketplaceOrderDetails($order, $isSentToAdmin, $isPlainText, $email) : void
    {
        // not a valid order email incoming from the action hook
        if (! $order instanceof WC_Order || ! $email instanceof WC_Email) {
            return;
        }

        // not an order email meant for admins, and not an email manually sent to customer (customer notes may not be flagged as manual emails)
        if (! $email instanceof WC_Email_Customer_Note && ! $isSentToAdmin && ! $email->is_manual()) {
            return;
        }

        try {
            $order = OrderAdapter::getNewInstance($order)->convertFromSource();
        } catch (Exception $exception) {
            // since we are in a hook callback context we bail early if the adapter can't convert to a native order
            return;
        }

        // not a Marketplaces order
        if (! $order->hasMarketplacesChannel()) {
            return;
        }

        $channelType = $order->getMarketplacesChannelType() ?: '';
        $channelLabel = ChannelRepository::getLabel($channelType, ! $isPlainText);
        $channelOrderNumber = $order->getMarketplacesDisplayOrderNumber() ?: '';

        $this->outputMarketplaceOrderDetails($channelLabel, $channelOrderNumber, (bool) $isPlainText);
    }

    /**
     * Outputs marketplace order details for WooCommerce emails.
     *
     * Match styles and formatting of WooCommerce emails.
     *
     * @param string $channelLabel
     * @param string $channelOrderNumber
     * @param bool $plainText
     * @return void
     */
    private function outputMarketplaceOrderDetails(string $channelLabel, string $channelOrderNumber, bool $plainText) : void
    {
        if (! $plainText) { // HTML emails
            ?>
            <div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
                <h2><?php esc_html_e('Marketplaces & Social Order', 'mwc-core'); ?></h2>
                <ul style="display: block; margin-left: 0; padding-inline-start: 0;">
                    <li style="list-style-type: none;"><?php
                        /* translators: Placeholder: %s - sales channel label */
                        printf(esc_html__('Sales Channel: %s', 'mwc-core'), $channelLabel); ?></li>
                    <li style='list-style-type: none;'><?php
                        /* translators: Placeholder: %s - sales channel order number */
                        printf(esc_html__('Sales Channel Order Number: %s', 'mwc-core'), esc_html($channelOrderNumber)); ?></li>
                </ul>
            </div>
            <?php
        } else { // plaintext emails
            echo esc_html__('Marketplaces & Social Order', 'mwc-core')."\n";
            /* translators: Placeholder: %s - sales channel label */
            printf(esc_html__('Sales Channel: %s', 'mwc-core')."\n", esc_html($channelLabel));
            /* translators: Placeholder: %s - sales channel order number */
            printf(esc_html__('Sales Channel Order Number: %s', 'mwc-core')."\n\n", esc_html($channelOrderNumber));
        }
    }
}
