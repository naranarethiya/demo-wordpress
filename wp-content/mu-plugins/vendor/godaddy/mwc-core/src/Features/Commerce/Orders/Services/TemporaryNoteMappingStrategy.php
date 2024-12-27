<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\NoteMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash\NoteHashService;

/**
 * @extends AbstractItemTemporaryMappingStrategy<Note>
 */
class TemporaryNoteMappingStrategy extends AbstractItemTemporaryMappingStrategy implements NoteMappingStrategyContract
{
    protected NoteHashService $noteHashService;

    /**
     * Constructor.
     *
     * @param NoteHashService $noteHashService
     */
    public function __construct(NoteHashService $noteHashService)
    {
        $this->noteHashService = $noteHashService;
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelHash(object $model) : string
    {
        return $this->noteHashService->getModelHash($model);
    }
}
