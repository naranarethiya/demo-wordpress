<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Note;

/**
 * @extends AbstractHashService<Note>
 */
class CommerceNoteHashService extends AbstractHashService
{
    /**
     * {@inheritDoc}
     */
    protected function getValuesForHash(object $model) : array
    {
        return [
            'Note',
            $model->content,
            (string) (int) $model->shouldNotifyCustomer,
            (string) $model->author,
        ];
    }
}
