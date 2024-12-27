<?php

namespace GoDaddy\WordPress\MWC\Common\Stores\Exceptions;

use GoDaddy\WordPress\MWC\Common\Stores\Repositories\AbstractStoreRepository;

/**
 * Exception that may be thrown by implementations of {@see AbstractStoreRepository::listStores()}.
 */
class ListStoresException extends StoreRepositoryException
{
}
