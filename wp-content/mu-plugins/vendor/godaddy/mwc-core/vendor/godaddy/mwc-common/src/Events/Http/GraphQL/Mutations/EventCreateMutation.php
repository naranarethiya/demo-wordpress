<?php

namespace GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Mutations;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

class EventCreateMutation extends AbstractGraphQLOperation
{
    /** @var string GraphQL operation */
    protected $operation = <<<'GQL'
mutation eventCreate(
  $userId: ID!
  $resource: String!
  $action: String!
  $data: String
) {
  eventCreate(
    input: {
      userId: $userId
      resource: $resource
      action: $action
      data: $data
    }
  ) {
    statusCode
    message
  }
}
GQL;

    /** @var EventBridgeEventContract */
    protected $event;

    /** @var User|null */
    protected $user;

    /**
     * Constructor.
     *
     * @param EventBridgeEventContract $event
     */
    public function __construct(EventBridgeEventContract $event)
    {
        $this->setEvent($event)->setAsMutation();
    }

    /**
     * {@inheritDoc}
     * @throws BaseException
     * @throws Exception
     */
    public function getVariables() : array
    {
        return ArrayHelper::combine(
            parent::getVariables(),
            [
                'userId'   => $this->getUserId(),
                'resource' => $this->getEvent()->getResource(),
                'action'   => $this->getEvent()->getAction(),
                'data'     => json_encode($this->getEventData()),
            ]
        );
    }

    /**
     * Attempts to retrieve the user ID.
     *
     * @return int The user ID, or 0 if no user is set.
     */
    protected function getUserId() : int
    {
        return $this->getUser() ? (int) $this->getUser()->getId() : 0;
    }

    /**
     * Gets the event.
     *
     * @return EventBridgeEventContract
     */
    public function getEvent() : EventBridgeEventContract
    {
        return $this->event;
    }

    /**
     * Sets the event.
     *
     * @param EventBridgeEventContract $value
     * @return $this
     */
    public function setEvent(EventBridgeEventContract $value) : EventCreateMutation
    {
        $this->event = $value;

        return $this;
    }

    /**
     * Gets the user.
     *
     * @return User|null
     */
    public function getUser() : ?User
    {
        return $this->user;
    }

    /**
     * Sets the user.
     *
     * @param User $value
     * @return $this
     */
    public function setUser(User $value) : EventCreateMutation
    {
        $this->user = $value;

        return $this;
    }

    /**
     * Gets the event data enhanced with data that we want to include with every event.
     *
     * @return array
     * @throws BaseException
     * @throws Exception
     */
    protected function getEventData() : array
    {
        return ArrayHelper::combineRecursive(
            $this->getEvent()->getData(),
            $this->getSiteProperties(),
            $this->getUserProperties()
        );
    }

    /**
     * Gets the site's properties.
     *
     * @return array<string, array<string, mixed>>
     * @throws PlatformRepositoryException
     */
    protected function getSiteProperties() : array
    {
        $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();

        return [
            'site' => [
                'id'               => $platformRepository->getSiteId(),
                'url'              => SiteRepository::getHomeUrl(),
                'xid'              => 'mwp' === $platformRepository->getPlatformName() ? (int) $platformRepository->getPlatformSiteId() : 0,
                'platform_site_id' => $platformRepository->getPlatformSiteId(),
                'uid'              => Configuration::get('godaddy.account.uid'),
                'active_plugins'   => WordPressRepository::getActivePlugins(),
                'active_features'  => ManagedExtensionsRepository::getEnabledFeatures(),
                'wc_version'       => WooCommerceRepository::getWooCommerceVersion(),
                'wp_version'       => WordPressRepository::getVersion(),
                'php_version'      => PHP_VERSION,
                'plan'             => $platformRepository->getPlan()->getName(),
                'isOnTrial'        => $platformRepository->getPlan()->isTrial(),
                'platform'         => $platformRepository->getPlatformName(),
                'venture_id'       => $platformRepository->getVentureId() ?: null,
                'channel_id'       => $platformRepository->getChannelId() ?: null,
                'customer_id'      => $platformRepository->getGoDaddyCustomerId() ?: null,
                'store_id'         => $platformRepository->getStoreRepository()->getStoreId(),
                'environment'      => ManagedWooCommerceRepository::getEnvironment(),
                'is_staging'       => $platformRepository->isStagingSite(),
                'isReseller'       => $platformRepository->isReseller(),
                'isTlaSite'        => $platformRepository->isTlaSite(),
            ],
        ];
    }

    /**
     * Gets the user's properties.
     *
     * @return array
     */
    protected function getUserProperties() : array
    {
        $user = $this->getUser();

        if (null === $user) {
            $user = User::getCurrent();
        }

        return [
            'user' => [
                'id' => $user ? $user->getId() : 0,
            ],
            'ip' => static::getClientIp(),
        ];
    }

    /**
     * Determines the user's actual IP address and attempts to partially
     * anonymize an IP address by converting it to a network ID.
     *
     * @see \WP_Community_Events::get_unsafe_client_ip()
     *
     * @return string|false
     */
    public static function getClientIp()
    {
        $clientIp = false;

        // in order of preference, with the best ones for this purpose first
        $addressHeaders = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($addressHeaders as $header) {
            if (ArrayHelper::has($_SERVER, $header)) {
                /*
                 * HTTP_X_FORWARDED_FOR can contain a chain of comma-separated
                 * addresses. The first one is the original client. It can't be
                 * trusted for authenticity, but we don't need to for this purpose.
                 */
                $addressChain = explode(',', $_SERVER[$header]);
                $clientIp = trim($addressChain[0]);

                break;
            }
        }

        if (! $clientIp) {
            return false;
        }

        $anonIp = wp_privacy_anonymize_ip($clientIp, true);

        if ('0.0.0.0' === $anonIp || '::' === $anonIp) {
            return false;
        }

        return $anonIp;
    }
}
