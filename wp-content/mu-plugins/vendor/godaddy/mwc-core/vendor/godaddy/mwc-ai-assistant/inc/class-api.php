<?php

namespace GoDaddy\MWC\WordPress\Assistant;

use WP_Error;
use WP_REST_Request;
use GoDaddy\MWC\WordPress\Assistant\GPTFunctions;

class API extends Assistant {

    public function __construct() {
        $this->load();
    }

    public function load(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the routes for the API.
     */
    public function register_routes(): void {
        register_rest_route('gd/v1', '/gd-assistant', [
            'methods' => 'POST',
            'callback' => [$this, 'handleRequest'],
            'permission_callback' => [$this, 'permissionCheck'],
        ]);

        register_rest_route('gd/v1', '/track', [
            'methods' => 'POST',
            'callback' => [$this, 'handleTracking'],
            'permission_callback' => [$this, 'permissionCheck'],
        ]);
    }

    /**
     * Route handler. Calls the AI and returns the response.
     * @param WP_REST_Request $request
     * @return WP_Error|mixed|object
     */

    public function handleRequest(WP_REST_Request $request) {

        $response = $this->callAI($request);


        if (is_wp_error($response)) {
            $error_string = $response->get_error_message();
            return new WP_Error('rest_error', $error_string);
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body);

        if (!is_object($json)) {
            return new WP_Error('rest_error', "The response does not contain a valid JSON object: {$body}");
        }

        if (isset($json->data)) {
            return $json->data;
        } else {
            $msg = isset($json->errors[0]) ? "Error: " . $json->errors[0]->message : "There was a problem";
            return new WP_Error('rest_json_error', $msg, $body);
        }
    }

    /**
     * Convert request options to the format expected by the API.
     * @param WP_REST_Request $request
     * @return array<string, mixed>
     */
    public function getRequestOptions($request): array {
        $options = $request->get_json_params()['options'];

        if (isset($options['functions'])) {
            // backwards compatibility
            return $options;
        }

        $gptFunctions = new GPTFunctions();

        $functions = [
            ...$gptFunctions->getWPFunctions(),
            ...$gptFunctions->getDocsFunctions(),
        ];

        if (is_plugin_active('woocommerce/woocommerce.php')) {
            $functions = array_merge($functions, $gptFunctions->getWooFunctions());
        }

        $gptTools = array_map(function ($function) {
            return [
                'type' => 'function',
                'value' => $function
            ];
        }, $functions);

        $options['tools'] = $gptTools;

        return $options;
    }

    /**
     * Calls the graphql API with the prompts. 
     * @param WP_REST_Request $request
     * @return array<string, mixed>|WP_Error
     */
    public function callAI($request) {

        $token = $this->getToken();

        if (is_wp_error($token)) {
            return $token;
        }

        $request_options = $this->getRequestOptions($request);

        // backwards compatibility, can remove this once all frontend/backend are updated
        $functionsOrTools = isset($request_options['functions']) ? "function { data\nname }" : "tools { id\ntype\nvalue { data\nname } }";

        $query = 'query($options: GocaasOptions!, $isWP: Boolean) {
            aiAssistant(options: $options, isWP: $isWP) {
              functionResponse {
                data
              }
              value {
                from
                content
                ' . $functionsOrTools . '
              }
              meta {
                id
                resolvedPrompt
              }
            }
          }';

        $response = wp_remote_request($this->getAssistantApiUrl(), [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'sslverify' => defined('GD_ASSISTANT_LOCAL') && GD_ASSISTANT_LOCAL ? false : true,
            'body' => (string) json_encode([
                'query' => $query,
                'variables' =>  [
                    'options' => $request_options,
                    'isWP' => true,
                ]
            ]),
            'timeout' => 60,
        ]);

        return $response;
    }

    /**
     * Route handler. Calls the AI and returns the response.
     * @param WP_REST_Request $request
     * @return WP_Error|mixed|object
     */

    public function handleTracking(WP_REST_Request $request) {

        $token = $this->getToken();

        if (is_wp_error($token)) {
            return $token;
        }

        $event = $request->get_json_params()['event'];

        $query = 'mutation Track($eventId: String!, $properties: JSON, $traceId: String) {
            track(eventId: $eventId, properties: $properties, traceId: $traceId) {
              message
            }
          }';

        $isMWP = class_exists('\WPaaS\Plugin') ? true : false;

        $event['properties']['source'] = $isMWP ? 'mwp' : 'mwcs';
        $event['properties']['siteurl'] = get_site_url();
        $event['properties']['referer'] = $request->get_header('referer');

        $response = wp_remote_request($this->getAssistantApiUrl(), [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'sslverify' => defined('GD_ASSISTANT_LOCAL') && GD_ASSISTANT_LOCAL ? false : true,
            'body' => (string) json_encode([
                'query' => $query,
                'variables' =>  [
                    'eventId' => $event['eventId'],
                    'properties' => $event['properties'],
                    'traceId' => $event['traceId']
                ]
            ]),
            'timeout' => 60,
        ]);

        return $response;
    }


    protected function getAssistantApiUrl(): string {
        return defined('GD_ASSISTANT_API_URL') ? GD_ASSISTANT_API_URL : '';
    }

    /**
     * Checks if the user has permission to access the API.
     * @return bool
     */
    public function permissionCheck() {
        return $this->isLocal() ? true : current_user_can('manage_options');
    }
}

new API();
