<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Support;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\GoDaddyRequest;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\SupportRequestFailedException;
use GoDaddy\WordPress\MWC\Dashboard\Repositories\WooCommercePluginsRepository;
use WP_REST_Server;

class SupportRequest extends AbstractModel
{
    /** @var string email the request is from. */
    protected $from;

    /** @var string The message to include with the support request. */
    protected $message;

    /** @var string The reason for the support request. */
    protected $reason;

    /** @var string The marketplace channels affected by the issue. */
    protected $affectedChannels;

    /** @var string The marketplace product SKUs affected by the issue. */
    protected $affectedSkus;

    /** @var string The subject of the support request. */
    protected $subject;

    /** @var string The extension this request is about. */
    protected $subjectExtension;

    /**
     * Sends a request to the Extensions API to create a support request.
     *
     * @throws Exception|SupportRequestFailedException
     */
    public function send()
    {
        $response = GoDaddyRequest::withAuth()
            ->setUrl(StringHelper::trailingSlash(ManagedWooCommerceRepository::getApiUrl()).'support/request')
            ->setBody([
                'data' => $this->getFormattedRequestData(),
                'from' => $this->getFrom(),
            ])
            ->setMethod('POST')
            ->send();

        if ($response->isError() || $response->getStatus() !== 200) {
            throw new SupportRequestFailedException("Could not send the support request ({$response->getStatus()}): {$response->getErrorMessage()}");
        }
    }

    /**
     * Gets the formatted data for the request.
     *
     * @return array
     * @throws PlatformRepositoryException
     */
    protected function getFormattedRequestData() : array
    {
        $requestingUser = $this->getRequestingUser();
        $subjectExtension = $this->getSubjectExtension();
        $supportUser = User::getByEmail(Configuration::get('support.support_user.email'))
            ?: User::getByHandle(Configuration::get('support.support_user.login'));

        if ($requestingUser) {
            $customerName = $requestingUser->getFullName() ?: $requestingUser->getHandle();
        } else {
            $customerName = '';
        }

        $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();

        $data = [
            'ticket' => [
                'subject'          => $this->getSubject(),
                'description'      => $this->getMessage(),
                'affectedChannels' => $this->getAffectedChannels(),
                'affectedSkus'     => $this->getAffectedSkus(),
            ],
            'channel' => [
                'id'         => $platformRepository->getChannelId(),
                'domain'     => SiteRepository::getDomain(),
                'plan'       => $platformRepository->getPlan()->getName() ?: null,
                'platform'   => $platformRepository->getPlatformName(),
                'is_staging' => $platformRepository->isStagingSite(),
            ],
            'customer' => [
                'id'          => $platformRepository->getGoDaddyCustomerId(),
                'name'        => $customerName,
                'email'       => $this->getFrom(),
                'is_reseller' => $platformRepository->isReseller(),
            ],
            'reason' => $this->getReason(),
            'plugin' => [
                'name'             => ArrayHelper::get($subjectExtension, 'Name', ''),
                'version'          => ArrayHelper::get($subjectExtension, 'Version', ''),
                'support_end_date' => ! empty($subjectExtension)
                    ? WooCommercePluginsRepository::getWooCommerceSubscriptionEnd($subjectExtension)
                    : '',
            ],
            'support_bot_context'  => Support::getConnectType(),
            'system_status_report' => $this->getSystemStatus(),
            'mwp'                  => [
                'is_reseller' => $platformRepository->isReseller(),
                'plan'        => [
                    'type' => $platformRepository->getPlan()->getName() ?: null,
                ],
            ],
        ];

        if ($supportUser) {
            ArrayHelper::set($data, 'support_user.user_id', $supportUser->getId());
            ArrayHelper::set($data, 'support_user.user_login', $supportUser->getHandle());
            ArrayHelper::set($data, 'support_user.password_reset_url', $supportUser->getPasswordResetUrl());
        }

        return $data;
    }

    /**
     * Get the email the request is coming from.
     *
     * @return string|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Get the request message.
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the reason for the request.
     *
     * @return string|null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Get the affected marketplace channels for the request.
     *
     * @return string|null
     */
    public function getAffectedChannels()
    {
        return $this->affectedChannels;
    }

    /**
     * Get the affected marketplace product SKUs for the request.
     *
     * @return string|null
     */
    public function getAffectedSkus()
    {
        return $this->affectedSkus;
    }

    /**
     * Gets the user making the request.
     *
     * @return User
     */
    private function getRequestingUser()
    {
        if ($this->getFrom()) {
            return User::getByEmail($this->getFrom()) ?: User::getCurrent();
        }

        return User::getCurrent();
    }

    /**
     * Get the subject of the request.
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get the extension this request is about.
     *
     * @return array
     */
    public function getSubjectExtension() : array
    {
        if ($this->subjectExtension) {
            return WooCommercePluginsRepository::getPluginDataBySlug($this->subjectExtension);
        }

        return [];
    }

    /**
     * Set the from address of the requesting user.
     *
     * @param string $email
     *
     * @return SupportRequest
     */
    public function setFrom(string $email) : SupportRequest
    {
        $this->from = $email;

        return $this;
    }

    /**
     * Set the message of the Request.
     *
     * @param string $message
     *
     * @return SupportRequest
     */
    public function setMessage(string $message) : SupportRequest
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set the reason for the request.
     *
     * @param string $reason
     *
     * @return SupportRequest
     */
    public function setReason(string $reason) : SupportRequest
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Set the affected marketplace channels for the request.
     *
     * @param string $channels
     *
     * @return SupportRequest
     */
    public function setAffectedChannels(string $channels) : SupportRequest
    {
        $this->affectedChannels = $channels;

        return $this;
    }

    /**
     * Set the affected marketplace SKUs for the request.
     *
     * @param string $skus
     *
     * @return SupportRequest
     */
    public function setAffectedSkus(string $skus) : SupportRequest
    {
        $this->affectedSkus = $skus;

        return $this;
    }

    /**
     * Set the subject of the request.
     *
     * @param string $subject
     *
     * @return SupportRequest
     */
    public function setSubject(string $subject) : SupportRequest
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set the subject extension.
     *
     * @param string $extensionSlug
     *
     * @return SupportRequest
     */
    public function setSubjectExtension(string $extensionSlug) : SupportRequest
    {
        $this->subjectExtension = $extensionSlug;

        return $this;
    }

    /**
     * Get the WC system status data.
     *
     * @return array<string, mixed>
     */
    protected function getSystemStatus() : array
    {
        // TODO: Move the logic to get the system status into a repository method and cache or memoize the result.
        //       https://godaddy-corp.atlassian.net/browse/MWC-17009
        if (! function_exists('rest_do_request') || ! function_exists('rest_get_server')) {
            return [];
        }

        $response = rest_do_request('/wc/v3/system_status');
        $server = rest_get_server();

        if (! $server instanceof WP_REST_Server) {
            return [];
        }

        return TypeHelper::array($server->response_to_data($response, false), []);
    }

    /**
     * Saves the support request.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function save() : SupportRequest
    {
        parent::save();

        Events::broadcast($this->buildEvent('support_request', 'create'));

        return $this;
    }
}
