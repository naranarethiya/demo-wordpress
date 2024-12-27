<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Contracts\NoteContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Contracts\CanPersistMultiItemsRemoteIdsContract;

/**
 * @extends CanPersistMultiItemsRemoteIdsContract<NoteContract>
 */
interface MultiNotesPersistentMappingServiceContract extends CanPersistMultiItemsRemoteIdsContract
{
}
