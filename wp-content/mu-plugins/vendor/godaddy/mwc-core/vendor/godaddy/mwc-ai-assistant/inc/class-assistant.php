<?php

namespace GoDaddy\MWC\WordPress\Assistant;

use Throwable;
use WP_Error;

class Assistant {

    public function __construct() {
        $this->start();
    }

    public function start(): void {

        if (!defined('GD_ASSISTANT_URL')) {
            define('GD_ASSISTANT_URL', plugin_dir_url(__DIR__));
        }

        define('GD_ASSISTANT_DIR', plugin_dir_path(__DIR__));

        define('GD_ASSISTANT_VERSION', '0.3.1');
        define('GD_ASSISTANT_SCRIPT_VERSION', '0.4.0'); // scripts are loaded from aws

        if (!defined('GD_ASSISTANT_API_URL')) {
            define('GD_ASSISTANT_API_URL', 'https://ai-assistant.api.godaddy.com/graphqlexternal');
        }

        $this->loadFiles();
    }

    /**
     * Get MWC JWT, used for auth with the API backend.
     *
     * @return string|WP_Error
     */
    protected function getToken() {
        if ($this->isLocal()) {
            return '';
        }

        if ($cachedToken = get_transient('gd_assistant_token')) {
            /** @var string $cachedToken */
            return $cachedToken;
        }

        if (class_exists(\GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory::class)) {
            $token = $this->getMWCStoken();
        } else {
            $token = $this->getMWPtoken();
        }

        if (!$token) {
            return new WP_Error('rest_error', "Cannot retrieve authentication token.");
        }

        set_transient('gd_assistant_token', $token, 3500);

        return $token;
    }

    /**
     * @return string|WP_Error
     */
    protected function getMWPtoken() {

        $account_id = defined('GD_ACCOUNT_UID') ? GD_ACCOUNT_UID : '';
        $site_token = defined('GD_SITE_TOKEN') ? GD_SITE_TOKEN : '';
        $site_id = $_SERVER['WPAAS_SITE_ID'] || $_SERVER['XID'];

        if (!$account_id || !$site_token || !$site_id) {
            return new WP_Error('rest_error', "Missing account id, site token, or site id.");
        }

        $response = wp_remote_post('https://api.mwc.secureserver.net/v1/token', array(
            'headers' => array(
                'X-Account-UID' => $account_id,
                'X-Site-Token' => $site_token,
                'X-Source' => 'mwp'
            ),
            'body' => array(
                'siteId' => $site_id,
                'userId' => wp_get_current_user()->ID
            )
        ));

        if (is_wp_error($response)) {
            return new WP_Error('rest_error', "Cannot retrieve authentication token.");
        }

        $body = wp_remote_retrieve_body($response);

        $body = json_decode($body, true);
        $token = is_array($body) ? $body['accessToken'] : '';

        return $token;
    }

    protected function getMWCStoken(): string {
        if (!class_exists(\GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory::class) || !class_exists(\GoDaddy\WordPress\MWC\Common\Auth\Providers\Models\Token::class)) {
            return '';
        }

        if (!is_callable([\GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory::class, 'getNewInstance'])) {
            return '';
        }

        try {
            $credentials = \GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory::getNewInstance()->getManagedWooCommerceAuthProvider()->getCredentials();
        } catch (Throwable $exception) {
            return '';
        }

        $token = $credentials instanceof \GoDaddy\WordPress\MWC\Common\Auth\Providers\Models\Token ? $credentials->getAccessToken() : '';

        return $token;
    }

    protected function isLocal(): string {
        return defined('GD_ASSISTANT_LOCAL') ? GD_ASSISTANT_LOCAL : false;
    }

    public function loadFiles(): void {
        require_once(dirname(__FILE__) . '/class-api.php');
        require_once(dirname(__FILE__) . '/class-admin.php');
        require_once(dirname(__FILE__) . '/class-gpt-functions.php');
    }
}
