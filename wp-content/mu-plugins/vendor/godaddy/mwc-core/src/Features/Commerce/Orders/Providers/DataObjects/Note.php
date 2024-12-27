<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\NoteAuthorType;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;

class Note extends AbstractDataObject
{
    /** @var non-empty-string|null */
    public ?string $id = null;

    /** @var string|null */
    public ?string $author;

    /** @var NoteAuthorType::* */
    public ?string $authorType;

    /** @var string */
    public string $content;

    /** @var non-empty-string|null */
    public ?string $createdAt = null;

    /** @var non-empty-string|null */
    public ?string $deletedAt = null;

    /** @var string|null */
    public ?string $referenceId = null;

    /** @var bool */
    public bool $shouldNotifyCustomer = false;

    /**
     * Constructor.
     *
     * @param array{
     *     id?: ?non-empty-string,
     *     author: ?string,
     *     authorType: NoteAuthorType::*,
     *     content: string,
     *     createdAt?: ?non-empty-string,
     *     deletedAt?: ?non-empty-string,
     *     referenceId?: ?string,
     *     shouldNotifyCustomer?: bool
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
