<?php

namespace GoDaddy\WordPress\MWC\Common\Sync\Jobs;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Common\Sync\Exceptions\JobNotFoundException;
use GoDaddy\WordPress\MWC\Common\Sync\Exceptions\MissingIdException;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;

/**
 * Base model of a sync job.
 */
class SyncJob extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /** @var int unique identifier of the job */
    protected $id;

    /** @var int timestamp when the job was created */
    protected $createdAt;

    /** @var int timestamp when the job was last updated */
    protected $updatedAt;

    /** @var int the number of objects that should be handled in a single request */
    protected $batchSize;

    /** @var string identifier of the job's initiator, such as a feature, component or plugin */
    protected $owner;

    /** @var string the current status of the job */
    protected $status;

    /** @var string type of object, such as order or customer */
    protected $objectType;

    /** @var int[] list of object identifiers that the job is expected to handle */
    protected $objectIds = [];

    /** @var int[] list of identifiers of objects that were created by the job */
    protected $createdIds = [];

    /** @var int[] list of identifiers of objects that were updated by the job */
    protected $updatedIds = [];

    /** @var string[] any errors occurred in job processing, with and properties */
    protected $errors = [];

    /** @var string used to store sync jobs */
    protected static $prefix = 'mwc_sync_job_';

    /**
     * Creates a new sync job and saves it.
     *
     * @param array<string, mixed> $data associative array of job properties
     * @return static
     * @throws Exception
     */
    public static function create(array $data = []) : SyncJob
    {
        return static::seed($data)
            ->setCreatedAt((new DateTime('now'))->getTimestamp())
            ->save();
    }

    /**
     * Gets a sync job from storage.
     *
     * @param int $id sync job ID
     * @return static|null
     */
    public static function get($id)
    {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare("
            SELECT option_value as value
            FROM {$wpdb->options}
            WHERE option_name LIKE %s
            AND option_id=%d
            LIMIT 1
        ", static::$prefix.'%', (int) $id), ARRAY_A);

        if (! $value = ArrayHelper::get($result, 'value')) {
            return null;
        }

        if (! $data = TypeHelper::array(json_decode(TypeHelper::ensureString($value), true), [])) {
            return null;
        }

        return static::seed($data)->setId((int) $id);
    }

    /**
     * Constructor.
     */
    final public function __construct()
    {
        // final constructor used to ensure that all subclasses can be instantiated without parameters
    }

    /**
     * Updates a sync job.
     *
     * @param array<string,mixed> $data properties to update
     * @return $this
     * @throws Exception
     */
    public function update(array $data = []) : SyncJob
    {
        global $wpdb;

        if (! $this->id) {
            throw new MissingIdException('Unable to update sync job: missing job ID.');
        }

        if (! static::get($this->id)) {
            throw new JobNotFoundException('Unable to update sync job: job could not be found.');
        }

        $this->setProperties($data);
        $this->setUpdatedAt((new DateTime('now'))->getTimestamp());

        $wpdb->update(
            $wpdb->options,
            ['option_value' => json_encode($this->toArray())],
            ['option_id'    => $this->id]
        );

        return $this;
    }

    /**
     * Deletes a sync job.
     *
     * @return bool
     */
    public function delete() : bool
    {
        global $wpdb;

        if (! $this->id) {
            return false;
        }

        return (bool) $wpdb->delete(
            $wpdb->options,
            ['option_id' => $this->id],
            ['%d']
        );
    }

    /**
     * Saves the sync job in its current state.
     *
     * @return $this
     * @throws Exception
     */
    public function save() : SyncJob
    {
        if (! empty($this->id)) {
            return $this->update();
        }

        $db = DatabaseRepository::instance();

        /**
         * Inserts the sync job as a WordPress option in WPDB.
         *
         * Note that the "autoload" value of the option is set to "no", otherwise WordPress would load all the sync jobs at every page load.
         * Autoloading options makes sense when the option needs to be accessed frequently, but in this case the sync jobs are only used when the sync process is triggered.
         *
         * @see \wp_load_alloptions() - this function would load all the options with autoload set to "yes" into memory on every page load
         * @link https://developer.wordpress.org/reference/functions/wp_load_alloptions/
         */
        $id = DatabaseRepository::insert(
            $db->options,
            [
                'option_name'  => $this->generateName(),
                'option_value' => json_encode($this->toArray()),
                'autoload'     => 'no', // do not autoload sync jobs options for performance reasons
            ],
            [
                '%s',
                '%s',
                '%s',
            ]
        );

        return $this->setId($id);
    }

    /**
     * Seeds a sync job instance without saving it.
     *
     * @param array<string, mixed> $data associative array of job properties
     * @return static
     */
    public static function seed(array $data = []) : SyncJob
    {
        return (new static())->setProperties(ArrayHelper::where($data, static function ($value) {
            return null !== $value;
        }));
    }

    /**
     * Generates the sync job unique name.
     *
     * @return string
     */
    protected function generateName() : string
    {
        return uniqid(static::$prefix, false);
    }

    /**
     * Sets the sync job ID.
     *
     * @param int $value
     * @return $this
     */
    public function setId(int $value) : SyncJob
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the sync job ID.
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Sets the sync job created at timestamp.
     *
     * @param int $value
     * @return $this
     */
    public function setCreatedAt(int $value) : SyncJob
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Gets the sync job created at timestamp.
     *
     * @return int
     */
    public function getCreatedAt() : int
    {
        return $this->createdAt;
    }

    /**
     * Sets the sync job updated at timestamp.
     *
     * @param int $value
     * @return $this
     */
    public function setUpdatedAt(int $value) : SyncJob
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Gets the sync job updated at timestamp.
     *
     * @return int
     */
    public function getUpdatedAt() : int
    {
        return $this->updatedAt;
    }

    /**
     * Sets the sync job batch size.
     *
     * @param int $value
     * @return $this
     */
    public function setBatchSize(int $value) : SyncJob
    {
        $this->batchSize = $value;

        return $this;
    }

    /**
     * Gets the sync job batch size.
     *
     * @return int
     */
    public function getBatchSize() : int
    {
        return $this->batchSize;
    }

    /**
     * Sets the sync job owner.
     *
     * @param string $value
     * @return $this
     */
    public function setOwner(string $value) : SyncJob
    {
        $this->owner = $value;

        return $this;
    }

    /**
     * Gets the sync job owner.
     *
     * @return string
     */
    public function getOwner() : string
    {
        return $this->owner;
    }

    /**
     * Sets the sync job status.
     *
     * @param string $value
     * @return $this
     */
    public function setStatus(string $value) : SyncJob
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Gets the sync job status.
     *
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * Sets the sync job owner.
     *
     * @param string $value
     * @return $this
     */
    public function setObjectType(string $value) : SyncJob
    {
        $this->objectType = $value;

        return $this;
    }

    /**
     * Gets the sync job owner.
     *
     * @return string
     */
    public function getObjectType() : string
    {
        return $this->objectType;
    }

    /**
     * Sets the sync job object IDs.
     *
     * @param int[] $value
     * @return $this
     */
    public function setObjectIds(array $value) : SyncJob
    {
        $this->objectIds = $value;

        return $this;
    }

    /**
     * Gets the sync job object IDs.
     *
     * @return int[]
     */
    public function getObjectIds() : array
    {
        return $this->objectIds;
    }

    /**
     * Sets the sync job created object IDs.
     *
     * @param int[] $value
     * @return $this
     */
    public function setCreatedIds(array $value) : SyncJob
    {
        $this->createdIds = $value;

        return $this;
    }

    /**
     * Gets the sync job created object IDs.
     *
     * @return int[]
     */
    public function getCreatedIds() : array
    {
        return $this->createdIds;
    }

    /**
     * Sets the sync job updated object IDs.
     *
     * @param int[] $value
     * @return $this
     */
    public function setUpdatedIds(array $value) : SyncJob
    {
        $this->updatedIds = $value;

        return $this;
    }

    /**
     * Gets the sync job updated object IDs.
     *
     * @return int[]
     */
    public function getUpdatedIds() : array
    {
        return $this->updatedIds;
    }

    /**
     * Sets the sync job errors.
     *
     * @param string[] $value
     * @return $this
     */
    public function setErrors(array $value) : SyncJob
    {
        $this->errors = $value;

        return $this;
    }

    /**
     * Adds the provided errors to the errors already set.
     *
     * @param string[] $value
     * @return $this
     * @throws Exception
     */
    public function addErrors(array $value) : SyncJob
    {
        $errors = TypeHelper::arrayOfStrings(ArrayHelper::combine($this->errors, $value));
        $this->errors = $errors;

        return $this;
    }

    /**
     * Gets the sync job errors.
     *
     * @return string[]
     */
    public function getErrors() : array
    {
        return $this->errors;
    }
}
