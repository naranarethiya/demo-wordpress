<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Builders\Orders;

use DateTime;
use DateTimeImmutable;
use GoDaddy\WordPress\MWC\Common\Builders\Contracts\BuilderContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use stdClass;

/**
 * Builds a {@see Note} from WC_Order note data, which is a stdClass.
 */
class OrderNoteBuilder implements BuilderContract
{
    use CanGetNewInstanceTrait;

    /** @var stdClass WooCommerce order note object */
    protected stdClass $data;

    /**
     * {@inheritDoc}
     */
    public function build() : Note
    {
        $note = Note::seed([
            'content'              => TypeHelper::string($this->data->content, ''),
            'authorName'           => TypeHelper::string($this->data->added_by, '') ?: null,
            'shouldNotifyCustomer' => (bool) $this->data->customer_note,
        ]);

        if ($this->data->date_created instanceof DateTime) {
            $note->setCreatedAt(DateTimeImmutable::createFromMutable($this->data->date_created));
        }

        if ($id = TypeHelper::int($this->data->id, 0)) {
            $note->setId($id);
        }

        return $note;
    }

    /**
     * Set WooCommerce order note object as data to build from.
     *
     * @param stdClass $value
     *
     * @return $this
     */
    public function setData(stdClass $value) : OrderNoteBuilder
    {
        $this->data = $value;

        return $this;
    }
}
