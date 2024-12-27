<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Gateways;

use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters\AccountAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\Account;
use Stripe\Exception\ApiErrorException;

/**
 * Account gateway.
 */
class AccountGateway extends StripeGateway
{
    /**
     * Returns a related Account model.
     *
     * @param string $id
     * @return Account
     * @throws ApiErrorException
     */
    public function get(string $id) : Account
    {
        $this->maybeLogApiRequest(__METHOD__, ['id' => $id]);
        $account = $this->getClient()->accounts->retrieve($id);
        $this->maybeLogApiResponse(__METHOD__, $account);

        return AccountAdapter::getNewInstance(Account::getNewInstance())->convertToSource($account->toArray());
    }
}
