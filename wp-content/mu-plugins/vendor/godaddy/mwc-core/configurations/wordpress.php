<?php

use GoDaddy\WordPress\MWC\Core\Events\Site\SiteDescriptionEvent;
use GoDaddy\WordPress\MWC\Core\Events\Site\SiteLogoEvent;
use GoDaddy\WordPress\MWC\Core\Events\Site\SiteTitleEvent;

return [
    /*
     * --------------------------------------------------------------------------
     * Permalinks
     * --------------------------------------------------------------------------
     *
     * Allow plain permalinks. Since some MWCS features break when plain permalinks are set, we force "post name"
     * permalinks via `EnforcePostNamePermalinksInterceptor` by default.  This constant enables overriding that
     * feature in wp-config.php.
     *
     */
    'permalinks' => [
        'allowPlain' => defined('MWC_PERMALINKS_ALLOW_PLAIN') ? MWC_PERMALINKS_ALLOW_PLAIN : false,
    ],

    /*
     * --------------------------------------------------------------------------
     * Plugins
     * --------------------------------------------------------------------------
     */
    'plugins' => [
        /*
         * An array of blocked plugins directory slugs.
         */
        'blocked' => [
            '6scan-backup',
            'adminer',
            'adsense-click-fraud-monitoring',
            'akeebabackupwp',
            'automatic-wordpress-backup',
            'backjacker',
            'backup',
            'backup-db',
            'backup-to-dropbox',
            'backupbuddy',
            'backupbuddy2.2.33',
            'backupcreator',
            'backupwordpress',
            'backupwp',
            'backwpup',
            'broken-link-checker',
            'broken-link-finder',
            'cache-images',
            'clef',
            'contextual-related-posts',
            'counterize',
            'db-cache-reloaded',
            'dbc-backup',
            'delete-all-comments',
            'disable plugin updates',
            'display-widgets',
            'exploit-scanner',
            'ezpz-one-click-backup',
            'facebook',
            'firestats',
            'fuzzy-seo-booster',
            'google-sitemap-generator',
            'google-xml-sitemaps-with-multisite-support',
            'gosquared-livestats',
            'hcs-client',
            'hello.php',
            'hyper-cache',
            'iwp-client',
            'jr-referrer',
            'mailpoet',
            'newstatpress',
            'nextgen-gallery',
            'p3-profiler',
            'pipdig',
            'portable-phpmyadmin',
            'pressbackup',
            'real-time-find-and-replace',
            'referrer-wp',
            'repress',
            'search-unleashed',
            'sendpress-email-marketing',
            'seo-alrp',
            'sgcachepress',
            'similar-posts',
            'simple-backup',
            'simple-stats',
            'simple-wordpress-backup',
            'slick-popup',
            'smestorage-multi-cloud-files-p',
            'snapshot',
            'snapshot-backup',
            'statpress',
            'statpress-reloaded',
            'statpress-visitors',
            'stats',
            'synthesis',
            'the-codetree-backup',
            'timthumb-vulnerability-scanner',
            'toolspack',
            'total-archive-by-fotan',
            'total-backup',
            'track-that-stat',
            'updraft',
            'updraftplus',
            'viberspy-pro',
            'visitor-stats-widget',
            'vm-backups',
            'vsf-simple-stats',
            'w3-total-cache',
            'wassup',
            'wordpress-backup',
            'wordpress-backup-to-dropbox',
            'wordpress-beta-tester',
            'wordpress-database-backup',
            'wordpress-popular-posts',
            'wp-cache',
            'wp-cachecom',
            'wp-complete-backup',
            'wp-copysafe-pdf',
            'wp-copysafe-web',
            'wp-database-backup',
            'wp-database-optimizer',
            'wp-db-backup',
            'wp-dbmanager',
            'wp-engine-snapshot',
            'wp-fast-cache',
            'wp-fastest-cache',
            'wp-file-cache',
            'wp-live-chat-support',
            'wp-mailinglist',
            'wp-maintenance-mode',
            'wp-optimize',
            'wp-phpmyadmin',
            'wp-postviews',
            'wp-power-stats',
            'wp-s3-backups',
            'wp-slimstat',
            'wp-statistics',
            'wp-super-cache',
            'wp-time-machine',
            'wpdbspringclean',
            'wpengine-common',
            'wponlinebackup',
            'xcloner-backup-and-restore',
            'xml-sitemap-feed',
            'yet-another-featured-posts-plugin',
            'youtube-sidebar-widget',
        ],
        /*
         * An array with information about plugins that must not be manually updated, deactivated or deleted.
         */
        'locked' => [
            [
                'name'     => 'WooCommerce',
                'basename' => 'woocommerce/woocommerce.php',
            ],
        ],
    ],
    /*
     * --------------------------------------------------------------------------
     * Monitored Items
     * --------------------------------------------------------------------------
     *
     * WordPress has some common data structures. Any data structures listed below will be monitored using Event Bridge.
     *
     */
    'monitoredItems' => [
        'options' => [
            'site_logo'       => [SiteLogoEvent::class],
            'blogname'        => [SiteTitleEvent::class],
            'blogdescription' => [SiteDescriptionEvent::class],
        ],
    ],
];
