<?php

namespace GoDaddy\MWC\WordPress\Assistant;

use WP_Admin_Bar;

class AdminFunctions {

    public function __construct() {
        $this->load();
    }

    public function load(): void {
        add_action('admin_enqueue_scripts', [$this, 'assistantScripts']);
        add_action('admin_footer', [$this, 'renderAssistant']);
        add_action('admin_bar_menu', [$this, 'addMenuLink'], 80);
        add_action('media_buttons', [$this, 'postEditTitleButtons'], 15);
    }

    public function postEditTitleButtons(): void {
        global $post;
        if ($post && $post->post_type === 'product' && current_user_can('manage_options')) {
            echo '<button type="button" class="gd-assistant-open gd-assistant-post-edit-button button"><span class="gd-assistant-dot"></span> Assistant</button>';
        }
    }

    /**
     * Adds a link to the admin bar.
     * @param WP_Admin_Bar $admin_bar
     */
    public function addMenuLink($admin_bar): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $admin_bar->add_menu(array(
            'id'    => 'gd-assistant',
            'parent' => 'top-secondary',
            'group'  => null,
            // 'title' => '<img src="' . GD_ASSISTANT_URL . 'assets/gd-icon.png" />', //you can use img tag with image link. it will show the image icon Instead of the title.
            'href'  => '#',
            'title' => '<span class="gd-assistant-ping-container"><span class="gd-assistant-ping"></span><span class="gd-assistant-dot"></span></span> Assistant',
            'meta' => [
                'class' => 'gd-assistant-open'
            ]
        ));
    }

    /**
     * Adds the assistant app inside an iframe to the frontend footer.
     */
    public function renderAssistant(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        global $post;
        $nonce = wp_create_nonce('wp_rest');
        $params = '?rest_nonce=' . $nonce;

        $user = wp_get_current_user();
        $email = isset($user->user_email) ? $user->user_email : '';
        $params .= '&userEmail=' . $email;

        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            $postIdParam = $post && isset($post->ID) ? '&postId=' . $post->ID : '';
            $postTitleParam = $post && isset($post->post_title) ? '&postTitle=' . $post->post_title : '';

            if ($screen) {
                $params .= '&postType=' . $screen->id . '&screenBase=' . $screen->base . $postIdParam . $postTitleParam;
            }
        }

        echo '<div id="gd-assistant-container">';
        echo '<div id="gd-assistant-backdrop"></div>';
        echo '<iframe loading="lazy" id="gd-assistant-app" src="' . $this->getAssistantUrl() . 'dist/index.html' . $params . '" style="" frameborder="0" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>';
        echo '</div>';
    }

    protected function getAssistantUrl(): string {
        return defined('GD_ASSISTANT_URL') ? GD_ASSISTANT_URL : '';
    }

    protected function getScriptVersion(): string {
        if (defined('GD_ASSISTANT_DEBUG')) {
            return 'staging';
        }
        return defined('GD_ASSISTANT_SCRIPT_VERSION') ? GD_ASSISTANT_SCRIPT_VERSION : '0.3.9';
    }

    /**
     * Enqueues the assistant styles and scripts.
     */
    public function assistantScripts(): void {
        wp_enqueue_style('gd-assistant', $this->getAssistantUrl() . 'assets/gd-assistant.css', array(), $this->getAssistantVersion());

        wp_enqueue_script('gd-assistant', $this->getAssistantUrl() . 'assets/gd-assistant.js', array('jquery'), $this->getAssistantVersion(), true);
    }

    protected function getAssistantVersion(): string {
        return defined('GD_ASSISTANT_VERSION') ? GD_ASSISTANT_VERSION : '';
    }
}

new AdminFunctions();
