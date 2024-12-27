<?php

namespace GoDaddy\WordPress\MWC\Common\Content;

use Exception;
use GoDaddy\WordPress\MWC\Common\Content\Contracts\RenderableContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use WC_Data;
use WP_Post;
use WP_Posts_List_Table;

/**
 * Object representation of a posts table column as used in {@see WP_Posts_List_Table}.
 */
abstract class AbstractPostsTableColumn implements RenderableContract
{
    /** @var string post type associated with this column */
    protected $postType = '';

    /** @var string the slug for the column */
    protected $slug = '';

    /** @var string the name for the column */
    protected $name = '';

    /** @var int the priority for the filter that registers the column */
    protected $registerPriority = 10;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Gets the post type.
     *
     * @return string
     */
    public function getPostType() : string
    {
        return $this->postType;
    }

    /**
     * Sets the post type.
     *
     * @param string $postType
     * @return AbstractPostsTableColumn $this
     */
    public function setPostType(string $postType) : AbstractPostsTableColumn
    {
        $this->postType = $postType;

        return $this;
    }

    /**
     * Gets the slug.
     *
     * @return string
     */
    public function getSlug() : string
    {
        return $this->slug;
    }

    /**
     * Sets the slug.
     *
     * @param string $slug
     * @return AbstractPostsTableColumn $this
     */
    public function setSlug(string $slug) : AbstractPostsTableColumn
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Gets the name.
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @param string $name
     * @return AbstractPostsTableColumn $this
     */
    public function setName(string $name) : AbstractPostsTableColumn
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the register priority.
     *
     * @return int
     */
    public function getRegisterPriority() : int
    {
        return $this->registerPriority;
    }

    /**
     * Sets the register priority.
     *
     * @param int $registerPriority
     * @return AbstractPostsTableColumn $this
     */
    public function setRegisterPriority(int $registerPriority) : AbstractPostsTableColumn
    {
        $this->registerPriority = $registerPriority;

        return $this;
    }

    /**
     * Registers the table column hooks.
     *
     * @return void
     */
    protected function addHooks()
    {
        if (! $this->getPostType()) {
            return;
        }

        try {
            Register::filter()
                ->setGroup($this->getRegisterColumnFilterHook())
                ->setHandler([$this, 'register'])
                ->setPriority($this->getRegisterPriority())
                ->execute();

            Register::action()
                ->setGroup($this->getRenderColumnActionHook())
                ->setHandler([$this, 'maybeRender'])
                ->setArgumentsCount(2)
                ->execute();
        } catch (Exception $exception) {
            SentryException::getNewInstance('An error occurred trying to register handlers table column hooks.', $exception);
        }
    }

    /**
     * Gets the hook name for the render column action.
     *
     * @return string
     */
    protected function getRenderColumnActionHook() : string
    {
        return "manage_{$this->getPostType()}_posts_custom_column";
    }

    /**
     * Gets the hook name for registering the column.
     *
     * @return string
     */
    protected function getRegisterColumnFilterHook() : string
    {
        return "manage_{$this->getPostType()}_posts_columns";
    }

    /**
     * Adds an entry to the columns array and returns the array.
     *
     * @param array<string, string> $columns
     * @return array<string, string> $columns
     */
    public function register(array $columns) : array
    {
        $columns[$this->getSlug()] = $this->getName();

        return $columns;
    }

    /**
     * Calls {@see render()} if {@see shouldRender()} returns true.
     *
     * @param mixed|non-empty-string $slug
     * @param int|WC_Data $objectOrId WC_Data object or post ID
     * @return void
     */
    public function maybeRender($slug, $objectOrId)
    {
        $slug = TypeHelper::stringOrNull($slug);
        $id = $this->getIdentifier($objectOrId);

        if ($slug && $this->shouldRender($slug, $id)) {
            $this->render($id);
        }
    }

    /**
     * Returns the identifier for the given object or ID.
     *
     * @param WP_Post|mixed $idOrObject post ID or object
     * @return int
     */
    protected function getIdentifier($idOrObject) : int
    {
        return $idOrObject instanceof WP_Post ? $idOrObject->ID : TypeHelper::int($idOrObject, 0);
    }

    /**
     * Returns true if the given slug matches the column slug.
     *
     * @param non-empty-string $slug
     * @param int $id object ID
     * @return bool
     */
    protected function shouldRender(string $slug, int $id)
    {
        return $id && $slug === $this->getSlug();
    }

    /**
     * Renders the column content.
     *
     * @param int|null $id object ID
     * @return mixed|void
     */
    abstract public function render(?int $id = null);
}
