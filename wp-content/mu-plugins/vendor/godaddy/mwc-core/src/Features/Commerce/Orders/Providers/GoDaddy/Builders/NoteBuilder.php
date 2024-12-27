<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\NoteAuthorType;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Note;

/**
 * @extends AbstractDataObjectBuilder<Note>
 */
class NoteBuilder extends AbstractDataObjectBuilder
{
    /**
     * Creates a new Note data object using the current data as source.
     */
    public function build() : Note
    {
        return new Note([
            'author'               => $this->stringOrNull(ArrayHelper::get($this->data, 'author')),
            'authorType'           => NoteAuthorType::tryFrom(TypeHelper::string(ArrayHelper::get($this->data, 'authorType'), '')) ?? NoteAuthorType::None,
            'content'              => TypeHelper::string(ArrayHelper::get($this->data, 'content'), ''),
            'createdAt'            => $this->nonEmptyStringOrNull(ArrayHelper::get($this->data, 'createdAt')),
            'deletedAt'            => $this->nonEmptyStringOrNull(ArrayHelper::get($this->data, 'deletedAt')),
            'id'                   => $this->nonEmptyStringOrNull(ArrayHelper::get($this->data, 'id')),
            'shouldNotifyCustomer' => TypeHelper::bool(ArrayHelper::get($this->data, 'shouldNotifyCustomer'), false),
        ]);
    }
}
