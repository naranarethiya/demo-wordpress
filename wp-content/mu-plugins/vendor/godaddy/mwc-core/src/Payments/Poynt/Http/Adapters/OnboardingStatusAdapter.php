<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

/**
 * The onboarding status adapter.
 *
 * @since 2.10.0
 */
class OnboardingStatusAdapter implements DataSourceAdapterContract
{
    /** @var array */
    protected $source = [];

    /**
     * Gets the onboarding status.
     *
     * @return array
     */
    public function convertFromSource() : array
    {
        return $this->source;
    }

    /**
     * Converts an HTTP response to a set of status values.
     *
     * @param Response|null $response
     *
     * @return array
     * @throws Exception
     */
    public function convertToSource(Response $response = null) : array
    {
        if (! $response) {
            return $this->source;
        }

        $responseBody = $response->getBody() ?? [];

        foreach (ArrayHelper::wrap(ArrayHelper::get($responseBody, 'processingAccounts', [])) as $processingAccount) {
            // bail if any of the account IDs don't match what's stored
            if (! $this->isMwpProcessingAccount($processingAccount)) {
                continue;
            }

            $createdAt = (int) strtotime(TypeHelper::string(ArrayHelper::get($processingAccount, 'createdAt'), ''));

            $this->source = [
                'status'          => $this->convertAccountStatus($processingAccount),
                'createdAt'       => $createdAt ?: 0,
                'hasBankAccount'  => 'SET' === ArrayHelper::get($processingAccount, 'bankAccount', 'NOT_SET'),
                'depositsEnabled' => (bool) ArrayHelper::get($processingAccount, 'depositsEnabled', false),
                'paymentsEnabled' => (bool) ArrayHelper::get($processingAccount, 'paymentsEnabled', false),
                'actionsRequired' => ArrayHelper::get($processingAccount, 'actionsRequired', []),
            ];
        }

        return $this->source;
    }

    /**
     * Converts the given processing account data into a single status.
     *
     * @param array $processingAccount
     *
     * @return string
     */
    protected function convertAccountStatus(array $processingAccount) : string
    {
        $accountStatus = ArrayHelper::get($processingAccount, 'accountStatus', '');
        $actionsRequired = ArrayHelper::get($processingAccount, 'actionsRequired', []);
        $applicationStatus = ArrayHelper::get($processingAccount, 'applicationStatus', '');
        $riskDecision = ArrayHelper::get($processingAccount, 'riskDecision', '');

        switch ($accountStatus) {
            case 'CREATED':
            case 'ACTIVATED':
                $onboardingStatus = Onboarding::STATUS_CONNECTED;
                break;

            case 'DEACTIVATED':
                $onboardingStatus = Onboarding::STATUS_DEACTIVATED;
                break;
            case 'SUSPENDED':
                $onboardingStatus = ArrayHelper::get($processingAccount, 'paymentsEnabled', false) ? Onboarding::STATUS_NEEDS_ATTENTION : Onboarding::STATUS_SUSPENDED;
                break;
            case 'TERMINATED':
                $onboardingStatus = Onboarding::STATUS_TERMINATED;
                break;
            case 'CHURNED':
                $onboardingStatus = Onboarding::STATUS_DISCONNECTED;
                break;
            default:
                $onboardingStatus = Onboarding::STATUS_PENDING;
        }

        // if the account status isn't "final", tweak the onboarding status based on the application status
        if (! ArrayHelper::contains([
            'SUSPENDED',
            'TERMINATED',
            'CHURNED',
        ], $accountStatus)) {
            switch ($applicationStatus) {
                case 'IN_REVIEW':
                    $onboardingStatus = Onboarding::STATUS_PENDING;
                    break;
                case 'INCOMPLETE':
                    $onboardingStatus = Onboarding::STATUS_INCOMPLETE;
                    break;
                case 'DECLINED':
                    $onboardingStatus = Onboarding::STATUS_DECLINED;
                    break;
            }
        }

        // the application has been approved but the account not activated yet
        if ('NOT_CREATED' === $accountStatus && 'APPROVED' === $applicationStatus && 'APPROVED' === $riskDecision) {
            $onboardingStatus = Onboarding::STATUS_CONNECTING;
        // the account is activated, but they are being warned to completed verification
        } elseif ('ACTIVATED' === $accountStatus && ArrayHelper::accessible($actionsRequired) && ArrayHelper::contains($actionsRequired, 'COMPLETE_VERIFICATION_WARNING')) {
            $onboardingStatus = Onboarding::STATUS_NEEDS_ATTENTION;
        }

        return $onboardingStatus;
    }

    /**
     * Determines if the given processing account belongs to this site.
     *
     * @param array $processingAccount
     *
     * @return bool
     * @throws Exception
     */
    protected function isMwpProcessingAccount(array $processingAccount) : bool
    {
        // bail if this is not an account created by our integrations
        if (! ArrayHelper::contains([
            Configuration::get('payments.poynt.serviceType'),
            'hosting.entitlements', // special type for Payments by default
        ], ArrayHelper::get($processingAccount, 'serviceType'))) {
            return false;
        }

        $applicationId = ArrayHelper::get($processingAccount, 'accountId', ''); // they use a different key name for this
        $businessId = ArrayHelper::get($processingAccount, 'businessId', '');
        $serviceId = ArrayHelper::get($processingAccount, 'serviceId', '');

        // bail if any of the account IDs don't match what's stored
        if (
            $applicationId !== Poynt::getApplicationId()
            || $businessId !== Poynt::getBusinessId()
            || $serviceId !== Poynt::getServiceId()
        ) {
            return false;
        }

        return true;
    }
}
