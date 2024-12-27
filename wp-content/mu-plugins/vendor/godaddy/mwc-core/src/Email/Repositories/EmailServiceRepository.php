<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Repositories;

use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class EmailServiceRepository
{
    use CanGetNewInstanceTrait;

    /**
     * Get the site ID. With `woosaas` this is actually the ChannelID.
     *
     * @return string
     * @throws PlatformRepositoryException
     */
    public static function getSiteId() : string
    {
        $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();

        if ($platformRepository->getPlatformName() === 'woosaas') {
            return $platformRepository->getChannelId();
        }

        return $platformRepository->getSiteId();
    }
}
