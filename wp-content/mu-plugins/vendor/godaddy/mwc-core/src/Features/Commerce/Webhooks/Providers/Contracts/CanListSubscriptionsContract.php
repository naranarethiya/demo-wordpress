<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\ListSubscriptionsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\Contracts\ListSubscriptionsResponseContract;

interface CanListSubscriptionsContract
{
    /**
     * Lists subscriptions.
     *
     * @param ListSubscriptionsInput $input
     * @return ListSubscriptionsResponseContract
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    public function list(ListSubscriptionsInput $input) : ListSubscriptionsResponseContract;
}
