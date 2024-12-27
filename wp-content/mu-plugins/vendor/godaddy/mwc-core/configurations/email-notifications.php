<?php

use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\CartRecoveryEmailContent;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\CartRecoveryEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\SecondCartRecoveryEmailContent;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\SecondCartRecoveryEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\ThirdCartRecoveryEmailContent;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\ThirdCartRecoveryEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\CancelledOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\CompletedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\CustomerInvoiceEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\CustomerNoteEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\FailedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\ItemShippedEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\NewAccountEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\NewOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\OrderOnHoldEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\PartiallyRefundedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\ProcessingOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\RefundedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\ResetPasswordEmailNotification;

return [
    'enabled'             => 'yes' === get_option('mwc_email_notifications_enabled', 'yes'),
    'allow_for_resellers' => defined('DISABLE_ACCOUNT_RESTRICTION_FOR_MWC_FEATURES') && DISABLE_ACCOUNT_RESTRICTION_FOR_MWC_FEATURES,

    /*
     * A list of plugins that are in conflict with the email deliverability feature.
     *
     * We will not attempt to send emails using our emails service if one of these plugins is active.
     */
    'conflicts' => [
        'plugins' => [
            'easy-wp-smtp/easy-wp-smtp.php',
            'postman-smtp/postman-smtp.php',
            'post-smtp/postman-smtp.php',
            'wp-mail-bank/wp-mail-bank.php',
            'smtp-mailer/main.php',
            'gmail-smtp/main.php',
            'wp-mail-smtp/wp_mail_smtp.php',
            'wp-mail-smtp-pro/wp_mail_smtp.php',
            'smtp-mail/index.php',
            'bws-smtp/bws-smtp.php',
            'wp-sendgrid-smtp/wp-sendgrid-smtp.php',
            'sar-friendly-smtp/sar-friendly-smtp.php',
            'wp-gmail-smtp/wp-gmail-smtp.php',
            'cimy-swift-smtp/cimy_swift_smtp.php',
            'wp-easy-smtp/wp-easy-smtp.php',
            'wp-mailgun-smtp/wp-mailgun-smtp.php',
            'my-smtp-wp/my-smtp-wp.php',
            'wp-mail-booster/wp-mail-booster.php',
            'sendgrid-email-delivery-simplified/wpsendgrid.php',
            'wp-mail-smtp-mailer/wp-mail-smtp-mailer.php',
            'wp-amazon-ses-smtp/wp-amazon-ses.php',
            'postmark-approved-wordpress-plugin/postmark.php',
            'mailgun/mailgun.php',
            'sparkpost/wordpress-sparkpost.php',
            'wp-yahoo-smtp/wp-yahoo-smtp.php',
            'wp-ses/wp-ses.php',
            'turbosmtp/turbo-smtp-plugin.php',
            'wp-smtp/wp-smtp.php',
            'woocommerce-sendinblue-newsletter-subscription/woocommerce-sendinblue.php',
            'disable-emails/disable-emails.php',
            'wp-email-smtp/wp-email-smtp.php',
        ],
    ],

    /*
     * A list of all registered email notifications.
     *
     * All notifications are listed here, but each individual class determines whether it is actually available for use
     * by implementing the @see EmailNotification::isAvailable() method.
     *
     * The path for the structured content (MJML) file for each notification is also configured here.
     * @see \GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\EmailContentDataStore::getStructuredContentPath()
     *
     * If the email notification supports content settings other than the default ones, a content_class should also be configured here.
     * @see \GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\EmailContentDataStore::read()
     */
    'notifications' => [
        'cancelled_order' => [
            'class'                   => CancelledOrderEmailNotification::class,
            'structured_content_path' => 'admin-order.mjml',
        ],
        'cart_recovery' => [
            'class'                   => CartRecoveryEmailNotification::class,
            'structured_content_path' => 'cart-recovery.mjml',
            'content_class'           => CartRecoveryEmailContent::class,
        ],
        'customer_completed_order' => [
            'class'                   => CompletedOrderEmailNotification::class,
            'structured_content_path' => 'customer-order.mjml',
        ],
        'customer_item_shipped' => [
            'class'                   => ItemShippedEmailNotification::class,
            'structured_content_path' => 'customer-order.mjml',
        ],
        'customer_new_account' => [
            'class'                   => NewAccountEmailNotification::class,
            'structured_content_path' => 'user.mjml',
        ],
        'customer_on_hold_order' => [
            'class'                   => OrderOnHoldEmailNotification::class,
            'structured_content_path' => 'customer-order.mjml',
        ],
        'customer_partially_refunded_order' => [
            'class'                   => PartiallyRefundedOrderEmailNotification::class,
            'structured_content_path' => 'customer-order.mjml',
        ],
        'customer_processing_order' => [
            'class'                   => ProcessingOrderEmailNotification::class,
            'structured_content_path' => 'customer-order.mjml',
        ],
        'customer_refunded_order' => [
            'class'                   => RefundedOrderEmailNotification::class,
            'structured_content_path' => 'customer-order.mjml',
        ],
        'customer_reset_password' => [
            'class'                   => ResetPasswordEmailNotification::class,
            'structured_content_path' => 'user.mjml',
        ],
        'failed_order' => [
            'class'                   => FailedOrderEmailNotification::class,
            'structured_content_path' => 'admin-order.mjml',
        ],
        'new_order' => [
            'class'                   => NewOrderEmailNotification::class,
            'structured_content_path' => 'admin-order.mjml',
        ],
        'customer_note' => [
            'class'                   => CustomerNoteEmailNotification::class,
            'structured_content_path' => 'customer-order-note.mjml',
        ],
        'customer_invoice' => [
            'class'                   => CustomerInvoiceEmailNotification::class,
            'structured_content_path' => 'customer-order.mjml',
        ],
        'second_cart_recovery' => [
            'class'                   => SecondCartRecoveryEmailNotification::class,
            'structured_content_path' => 'cart-recovery.mjml',
            'content_class'           => SecondCartRecoveryEmailContent::class,
        ],
        'third_cart_recovery' => [
            'class'                   => ThirdCartRecoveryEmailNotification::class,
            'structured_content_path' => 'cart-recovery.mjml',
            'content_class'           => ThirdCartRecoveryEmailContent::class,
        ],
    ],
];
