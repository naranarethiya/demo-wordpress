<?php

namespace WPaaS\Admin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Product_Events {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_action( 'save_post', [ $this, 'log_post_changes' ], PHP_INT_MAX, 3 );
		add_action( 'delete_post', [ $this, 'log_post_changes' ], PHP_INT_MAX, 2 );

		add_action( 'activate_plugin',   [ $this, 'update_plugins_changed' ], PHP_INT_MAX, 2 );
		add_action( 'deactivate_plugin', [ $this, 'update_plugins_changed' ], PHP_INT_MAX, 2 );

	}

	/**
	 * Set the website_content_changed or store_products_changed value when a post, page or product is published, updated or deleted.
	 *
	 * @param  int     $post_id Post ID.
	 * @param  WP_Post $post Post object.
	 *
	 * @return null
	 */
	public function log_post_changes( $post_id, $post ) {

		if ( ! $this->check_permissions() ) {

			return;

		}

		switch ( $post->post_type ) {
			case 'post':
			case 'page':
				update_option( 'wpaas_website_content_changed', time() );
				break;

			case 'product':
				update_option( 'wpaas_store_products_changed', time() );
				break;
		}

	}

	/**
	 * Check if the user has the proper permissions.
	 *
	 * @return boolean True when the user has the proper permissions, false otherwise.
	 */
	private function check_permissions() {

		$user          = wp_get_current_user();
		$allowed_roles = [ 'administrator', 'author', 'contributor', 'editor', 'shop_manager' ];

		return array_intersect( $allowed_roles, $user->roles );

	}

	/*
	 * Update the 'plugins_changed' value anytime a plugin is activated or deactivated.
	 *
	 * @return null
	 */
	public function update_plugins_changed() {

		update_option( 'wpaas_plugins_changed', time() );

	}

}
