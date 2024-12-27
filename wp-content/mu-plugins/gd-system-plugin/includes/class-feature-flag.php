<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

    exit;

}

final class Feature_Flag {

    const CACHING_INTERVAL = 1800;

    const OPTION_NAME = 'wpaas_feature_flag_%s';

    const ROUTE = 'featureFlags';

    /**
     * Instance of the API.
     *
     * @var API_Interface
     */
    private $api;

    /**
     * @var bool
     */
    private $has_api_failed;

    public function __construct( API_Interface $api )
    {
        $this->api                     = $api;
        $GLOBALS['wpaas_feature_flag'] = $this;
        $this->has_api_failed          = false;
    }

    /**
     * Get feature flag
     *
     * @param string $flag_name
     * @param bool   $default_value
     *
     * @return bool
     */
    public function get_feature_flag_value( $flag_name, $default_value ) {
        if ( !defined('GD_ACCOUNT_UID') ) {
            return $default_value;
        }

        $cached_data = get_option( sprintf(self::OPTION_NAME, GD_ACCOUNT_UID) );

        if ( !$cached_data ) {
            // Return data from API. If API fails to fetch or requested flag doesn't exist return default value
            return $this->get_data_from_api( $flag_name, true) ?? $default_value;
        }

        if ( empty($cached_data['timestamp']) || time() - $cached_data['timestamp'] > self::CACHING_INTERVAL ) {
            // Return data from API. If API fails to fetch return cached value. If there is no requested flag in cache returns default value
             $data = $this->get_data_from_api( $flag_name, false );
			 if ( $data !== null ) {
				 return $data;
			 }

                    // Don't hammer the API in case of downtime. Reuse previous value
                     if ( $this->has_api_failed ) {
                         $cached_data['timestamp'] = time();
                         update_option(sprintf(self::OPTION_NAME, GD_ACCOUNT_UID), $cached_data);

                         return $this->get_cached_data_with_fallback($flag_name, $cached_data, $default_value);
                     }

                     // Return default value if API has succeed but the flag was deleted
                     return $default_value;
        }

        // Return cached value
        return $this->get_cached_data_with_fallback( $flag_name, $cached_data, $default_value );
    }

    /**
     *
     * @param string $flag_name
     * @param array  $cached_data
     * @param bool   $default_value
     *
     * @return bool
     */
    private function get_cached_data_with_fallback( $flag_name, $cached_data, $default_value ) {
        return $this->get_flag_value( $cached_data['flags'], $flag_name ) ?? $default_value;
    }

    /**
     * Get feature flag from API
     *
     * @param string $flag_name
     *
     * @return bool|null
     */
    private function get_data_from_api( $flag_name , $first_fetch) {
        $body = $this->api->fetch_feature_flag( sprintf('%s/%s', self::ROUTE, GD_ACCOUNT_UID) );

        if( null === $body && $first_fetch ) {
            $body = [];
            $body['flags'] = [];
            $this->has_api_failed = true;
        } else if ( null === $body ) {
            $this->has_api_failed = true;
            return null;
        }

        $body['timestamp'] = time();

        update_option(sprintf(self::OPTION_NAME, GD_ACCOUNT_UID), $body);

        if ( empty($body['flags']) ) {
            return null;
        }

        return $this->get_flag_value( $body['flags'], $flag_name );
    }

    /**
     * Search for requested flag name
     *
     * @param array  $data
     * @param string $flag_name
     *
     * @return bool|null
     */
    private function get_flag_value( $data, $flag_name ) {
        foreach ( $data as $item ) {
            if ( $item['title'] && $item['title'] === $flag_name ) {
                return $item['value'];
            }
        }

        return null;
    }
}
