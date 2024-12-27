<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;

/**
 * @extends AbstractHashService<Note>
 */
class NoteHashService extends AbstractHashService
{
    /**
     * {@inheritDoc}
     */
    protected function getValuesForHash(object $model) : array
    {
        return [
            'Note',
            (string) $model->getContent(),
            (string) (int) $model->getShouldNotifyCustomer(),
            (string) $model->getAuthorName(),
        ];
    }
}
