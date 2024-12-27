<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Contracts\NoteContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiNotesPersistentMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\NoteMappingServiceContract;

/**
 * @extends AbstractMultiItemsPersistentMappingService<NoteContract>
 */
class MultiNotesPersistentMappingService extends AbstractMultiItemsPersistentMappingService implements MultiNotesPersistentMappingServiceContract
{
    /**
     * Constructor.
     *
     * @param NoteMappingServiceContract $mappingService
     */
    public function __construct(NoteMappingServiceContract $mappingService)
    {
        parent::__construct($mappingService);
    }
}
