<?php

namespace GoDaddy\WordPress\MWC\Core\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Configuration\RuntimeConfigurationFactory;
use GoDaddy\WordPress\MWC\Core\Features\Categories;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresWooCommercePermissionsTrait;
use RuntimeException;
use WP_REST_Request;

/**
 * Features controller class.
 */
class FeaturesController extends AbstractController implements ComponentContract
{
    use RequiresWooCommercePermissionsTrait;

    /**
     * Route.
     *
     * @var string
     */
    protected $route = 'features';

    protected PlatformRepositoryContract $platformRepository;

    protected RuntimeConfigurationFactory $runtimeConfigurationFactory;

    public function __construct(
        RuntimeConfigurationFactory $runtimeConfigurationFactory,
        PlatformRepositoryContract $platformRepository
    ) {
        $this->runtimeConfigurationFactory = $runtimeConfigurationFactory;
        $this->platformRepository = $platformRepository;
    }

    /**
     * Initializes the controller.
     *
     * @return void
     */
    public function load() : void
    {
        $this->registerRoutes();
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes() : void
    {
        register_rest_route(
            $this->namespace,
            "/{$this->route}",
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'getItems'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Gets a REST response with the native features visible to the site admin.
     *
     * @param WP_REST_Request<array<mixed>> $request
     * @throws Exception
     */
    public function getItems(WP_REST_Request $request) : void
    {
        try {
            /** @var array<string, mixed> $allFeatures */
            $allFeatures = Configuration::get('features', []);
            $features = [];

            /** @var array<string, mixed> $featureData */
            foreach ($allFeatures as $featureId => $featureData) {
                // skip features without the name set (should not be displayed)
                if (empty($name = ArrayHelper::get($featureData, 'name'))) {
                    continue;
                }

                $features[$name] = $this->prepareItem($featureId, $featureData);
            }

            // sort alphabetically by name
            ksort($features);

            $responseData = ['features' => array_values($features)];

            (new Response)
                ->setBody($responseData)
                ->success(200)
                ->send();
        } catch (BaseException $exception) {
            (new Response)
                ->error([$exception->getMessage()], $exception->getCode())
                ->send();
        }
    }

    /**
     * Prepares the given feature data for API response.
     *
     * @param string $featureId
     * @param array<string, mixed> $featureData
     * @return array<string, mixed>
     * @throws ContainerException|RuntimeException
     */
    protected function prepareItem(string $featureId, array $featureData) : array
    {
        $configuration = $this->runtimeConfigurationFactory->getFeatureRuntimeConfiguration($featureId);

        return [
            'name'             => $configuration->getName(),
            'description'      => $configuration->getDescription(),
            'documentationUrl' => $this->getDocumentationUrl($configuration->getDocumentationUrl()),
            'settingsUrl'      => $configuration->getSettingsUrl(),
            'categories'       => $configuration->getCategories(),
            'enabled'          => $this->getFeatureEnabledStatus($featureData),
        ];
    }

    /**
     * Determines if a given feature should be enabled.
     *
     * @param array<string, mixed> $feature
     * @return bool
     */
    protected function getFeatureEnabledStatus(array $feature) : bool
    {
        $className = TypeHelper::string(ArrayHelper::get($feature, 'className'), '');

        if (empty($className) || ! is_a($className, AbstractFeature::class, true)) {
            return false;
        }

        if ($this->platformRepository->getGoDaddyCustomer()->getFederationPartnerId() === 'WORLDPAY') {
            $categories = TypeHelper::array(ArrayHelper::get($feature, 'categories'), []);
            if (ArrayHelper::contains($categories, Categories::Payments)) {
                return false;
            }
        }

        return $className::shouldBeVisible();
    }

    /**
     * Gets the documentation URL, modified for resellers, if applicable.
     *
     * @param string $originalUrl
     * @return string
     */
    protected function getDocumentationUrl(string $originalUrl) : string
    {
        if (empty($originalUrl) || ! $this->platformRepository->isReseller()) {
            return $originalUrl;
        }

        if (StringHelper::contains($originalUrl, '/godaddy.com/')) {
            $url = StringHelper::replaceFirst($originalUrl, '/godaddy.com/', '/www.secureserver.net/');
        } else {
            $url = $originalUrl;
        }

        if (! StringHelper::contains($url, '/www.secureserver.net/')) {
            return $url;
        }

        // append private label id
        if ($privateLabelId = $this->platformRepository->getResellerId()) {
            $url .= StringHelper::contains($url, '?') ? '&' : '?';
            $url .= "pl_id={$privateLabelId}";
        }

        return $url;
    }

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @return array<string, mixed>
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'feature',
            'type'       => 'object',
            'properties' => [
                'name' => [
                    'description' => __('The native feature name.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'description' => [
                    'description' => __('The native feature description.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'documentationUrl' => [
                    'description' => __('The native feature documentation URL.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'settingsUrl' => [
                    'description' => __('The native feature settings URL, if applicable.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'categories' => [
                    'description' => __('The native feature categories.', 'mwc-dashboard'),
                    'type'        => 'array',
                    'items'       => [
                        'type' => 'string',
                        'enum' => [
                            'Cart and Checkout',
                            'Marketing and Messaging',
                            'Merchandising',
                            'Payments',
                            'Product Type',
                            'Shipping',
                            'Store Management',
                        ],
                        'context'  => ['view', 'edit'],
                        'readonly' => true,
                    ],
                    'context'  => ['view', 'edit'],
                    'readonly' => true,
                ],
                'enabled' => [
                    'description' => __('Whether or not the native feature is enabled for this site.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
