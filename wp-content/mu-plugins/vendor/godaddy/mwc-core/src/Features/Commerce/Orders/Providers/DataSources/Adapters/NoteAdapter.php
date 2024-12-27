<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use DateTimeInterface;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Contracts\NoteContract;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\CustomerNote;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\NoteAuthorType;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Note as NoteDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters\DateTimeAdapter;

class NoteAdapter implements DataObjectAdapterContract
{
    protected DateTimeAdapter $dateTimeAdapter;

    public function __construct(DateTimeAdapter $dateTimeAdapter)
    {
        $this->dateTimeAdapter = $dateTimeAdapter;
    }

    /**
     * {@inheritDoc}
     * @param NoteDataObject $source
     */
    public function convertFromSource($source) : NoteContract
    {
        return $this->instantiateNoteModel($source)
            ->setContent($source->content)
            ->setAuthorName($source->author)
            ->setShouldNotifyCustomer($source->shouldNotifyCustomer)
            ->setCreatedAt($this->convertCreatedAtFromSource($source));
    }

    /**
     * Creates a new instance of {@see NoteContract} using the appropriate concrete type based for the given commerce note.
     *
     * @param NoteDataObject $source
     * @return NoteContract
     */
    protected function instantiateNoteModel(NoteDataObject $source) : NoteContract
    {
        if ($source->authorType === NoteAuthorType::Customer) {
            return new CustomerNote();
        }

        return new Note();
    }

    /**
     * {@inheritDoc}
     * @param NoteContract $target
     */
    public function convertToSource($target) : NoteDataObject
    {
        return new NoteDataObject([
            'author'               => $target->getAuthorName(),
            'authorType'           => $this->convertAuthorTypeToSource($target),
            'createdAt'            => $this->convertCreatedAtToSource($target),
            'content'              => TypeHelper::string($target->getContent(), ''),
            'shouldNotifyCustomer' => $target->getShouldNotifyCustomer(),
        ]);
    }

    /**
     * Converts NoteDataObject createdAt to {@see DateTimeInterface} object.
     *
     * @param NoteDataObject $source
     *
     * @return ?DateTimeInterface
     */
    protected function convertCreatedAtFromSource(NoteDataObject $source) : ?DateTimeInterface
    {
        return $this->dateTimeAdapter->convertFromSource($source->createdAt);
    }

    /**
     * Finds the correct NoteAuthorType enum for the given note.
     *
     * @param NoteContract $target
     * @return NoteAuthorType::*
     */
    protected function convertAuthorTypeToSource(NoteContract $target) : string
    {
        if ($target instanceof CustomerNote) {
            return NoteAuthorType::Customer;
        }

        return $target->isAddedBySystem() ? NoteAuthorType::None : NoteAuthorType::Merchant;
    }

    /**
     * Converts Note createdAt to string timestamp.
     *
     * @param NoteContract $target
     * @return non-empty-string|null
     */
    protected function convertCreatedAtToSource(NoteContract $target) : ?string
    {
        return $this->dateTimeAdapter->convertToSource($target->getCreatedAt());
    }
}
