<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Models;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CommerceContextRepository;
use InvalidArgumentException;

/**
 * @method static static getNewInstance(array{id?: ?int, storeId: string} $data)
 */
class CommerceContext extends AbstractModel implements CommerceContextContract
{
    protected ?int $id = null;

    protected string $storeId;

    /**
     * Seeds a new instance of the object.
     *
     * PHPStan reports that the default parameter of the method is not compatible with the documented type. That error
     * was added to the baseline because we can't change the signature of the method and we want to use array shape as
     * the documented type.
     *
     * @param array{id?: ?int, storeId: string} $data
     * @return static
     */
    public static function seed(array $data = [])
    {
        return new static($data);
    }

    /**
     * Constructor.
     *
     * @param array{id?: ?int, storeId: string} $data
     */
    final public function __construct(array $data)
    {
        $this->id = TypeHelper::int(ArrayHelper::get($data, 'id'), 0) ?: null;
        $this->storeId = $data['storeId'];
    }

    /**
     * @param int|null $value
     */
    public function setId(?int $value) : void
    {
        $this->id = $value;
    }

    /**
     * @return int|null
     */
    public function getId() : ?int
    {
        return $this->id ??= $this->findOrCreateContext();
    }

    /**
     * @return int|null
     */
    protected function findOrCreateContext() : ?int
    {
        try {
            return CommerceContextRepository::getInstance()->findOrCreateContextWithCache($this->storeId);
        } catch (InvalidArgumentException|WordPressDatabaseException $exception) {
            return null;
        }
    }

    /**
     * @param string $value
     */
    public function setStoreId(string $value) : void
    {
        $this->storeId = $value;
    }

    /**
     * @return string
     */
    public function getStoreId() : string
    {
        return $this->storeId;
    }
}
