<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Contracts\NoteContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\CustomerNote;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Repositories\CustomerNoteMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Repositories\NoteMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash\NoteHashService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategyFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;

class NoteMappingStrategyFactory extends AbstractMappingStrategyFactory
{
    protected CustomerNoteMapRepository $customerNoteMapRepository;

    protected NoteMapRepository $noteMapRepository;

    protected NoteHashService $noteHashService;

    public function __construct(
        CommerceContextContract $commerceContext,
        CustomerNoteMapRepository $customerNoteMapRepository,
        NoteMapRepository $noteMapRepository,
        NoteHashService $noteHashService
    ) {
        $this->customerNoteMapRepository = $customerNoteMapRepository;
        $this->noteMapRepository = $noteMapRepository;
        $this->noteHashService = $noteHashService;

        parent::__construct($commerceContext);
    }

    /**
     * Gets the primary mapping strategy for the given note.
     *
     * Primary strategies usually deal with records that already have a local ID.
     *
     * @param NoteContract $model
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?MappingStrategyContract
    {
        if ($model instanceof CustomerNote && $model->getOrderId()) {
            return new CustomerNoteMappingStrategy($this->customerNoteMapRepository);
        }

        if ($model instanceof Note && $model->getId()) {
            return new NoteMappingStrategy($this->noteMapRepository);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSecondaryMappingStrategy() : MappingStrategyContract
    {
        return new TemporaryNoteMappingStrategy($this->noteHashService);
    }
}
