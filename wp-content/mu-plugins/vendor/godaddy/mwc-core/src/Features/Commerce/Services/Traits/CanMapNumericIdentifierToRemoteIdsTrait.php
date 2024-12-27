<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Traits;

use GoDaddy\WordPress\MWC\Common\Contracts\HasNumericIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;

trait CanMapNumericIdentifierToRemoteIdsTrait
{
    /**
     * Maps given remote ID to the given model's local ID.
     *
     * @param HasNumericIdentifierContract $model
     * @param string $remoteId
     * @return void
     * @throws CommerceException
     */
    public function saveRemoteId(object $model, string $remoteId) : void
    {
        $this->saveMapping(TypeHelper::int($model->getId(), 0), $remoteId);
    }

    /**
     * Fetches mapped remote ID for the give model's local ID.
     *
     * @param HasNumericIdentifierContract $model
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string
    {
        return $this->resourceMapRepository->getRemoteId(TypeHelper::int($model->getId(), 0));
    }
}
