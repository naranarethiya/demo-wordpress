<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Contracts\NoteContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Note as CommerceNote;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiNotesMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\NoteMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash\CommerceNoteHashService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash\NoteHashService;

/**
 * @extends AbstractMultiItemsMappingService<NoteContract, CommerceNote>
 */
class MultiNotesMappingService extends AbstractMultiItemsMappingService implements MultiNotesMappingServiceContract
{
    public function __construct(
        NoteMappingServiceContract $mappingService,
        NoteHashService $localModelHashService,
        CommerceNoteHashService $commerceObjectHashService
    ) {
        parent::__construct($mappingService, $localModelHashService, $commerceObjectHashService);
    }
}
