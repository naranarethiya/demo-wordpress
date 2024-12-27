<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\UserAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\UserLogInException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;
use WP_Error;

/**
 * Native user object.
 */
class User extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;
    use HasNumericIdentifierTrait;

    /** @var string display name */
    protected $displayName;

    /** @var string email address */
    protected $email;

    /** @var string first name */
    protected $firstName;

    /** @var string login handle */
    protected $handle;

    /** @var string last name */
    protected $lastName;

    /** @var string nickname */
    protected $nickname;

    /**
     * Gets the user display name.
     *
     * @return string|null
     */
    public function getDisplayName() : ?string
    {
        return $this->displayName;
    }

    /**
     * Gets the user email.
     *
     * @return string|null
     */
    public function getEmail() : ?string
    {
        return $this->email;
    }

    /**
     * Gets the user first name.
     *
     * @return string|null
     */
    public function getFirstName() : ?string
    {
        return $this->firstName;
    }

    /**
     * Gets the full name, if available.
     *
     * @return string
     */
    public function getFullName() : string
    {
        if (! ($first = $this->getFirstName()) || ! ($last = $this->getLastName())) {
            return $this->getDisplayName() ?? '';
        }

        // @TODO some locales invert the position of the first and the last name and we might have to account for this in the future, maybe with a method argument? {FN 2021-03-19}
        return "{$first} {$last}";
    }

    /**
     * Gets the user handle.
     *
     * @return string|null
     */
    public function getHandle() : ?string
    {
        return $this->handle;
    }

    /**
     * Gets the user first name.
     *
     * @return string|null
     */
    public function getLastName() : ?string
    {
        return $this->lastName;
    }

    /**
     * Gets the user nickname.
     *
     * @return string|null
     */
    public function getNickname() : ?string
    {
        return $this->nickname;
    }

    /**
     * Gets the user password rest URL.
     *
     * @return string
     * @throws Exception
     */
    public function getPasswordResetUrl() : string
    {
        /* @phpstan-ignore-next-line */
        $passwordResetKey = get_password_reset_key(get_user_by('id', $this->id));

        if ($passwordResetKey instanceof WP_Error) {
            // @TODO: Update to specific exception after deciding the folder location of where that should live {JO: 2021-03-26}
            throw new SentryException($passwordResetKey->get_error_message());
        }

        $parameters = ArrayHelper::query([
            'action' => 'rp',
            'key'    => $passwordResetKey,
            'login'  => rawurlencode($this->getHandle() ?: ''),
        ]);

        // @TODO: Should really move the site url to a config given its useful in many places -- though maybe its a security issue since it can be overwritten {JO: 2021-03-26}
        /** @var string|mixed $url WordPress may filter this potentially to a non-string, so we ensure the type is the expected one */
        $url = network_site_url("wp-login.php?{$parameters}", 'login');

        return is_string($url) ? $url : '';
    }

    /**
     * Sets the user email.
     *
     * @param string $email
     * @return self
     */
    public function setEmail(string $email) : self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Sets the user display name.
     *
     * @param string $displayName
     * @return self
     */
    public function setDisplayName(string $displayName) : self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Sets the user first name.
     *
     * @param string $firstName
     * @return self
     */
    public function setFirstName(string $firstName) : self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Sets the user login handle.
     *
     * @param string $handle
     * @return self
     */
    public function setHandle(string $handle) : self
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * Sets the user last name.
     *
     * @param string $lastName
     * @return self
     */
    public function setLastName(string $lastName) : self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Sets the user nickname.
     *
     * @param string $nickname
     * @return self
     */
    public function setNickname(string $nickname) : self
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Creates a new User.
     *
     * @param array $data
     * @return self
     * @throws SentryException
     */
    public static function create(array $data = []) : User
    {
        return static::seed($data)->save();
    }

    /**
     * Deletes the given user instance.
     *
     * @return bool
     * @throws SentryException
     */
    public function delete(?int $reassignUserId = null) : bool
    {
        if (! $this->getId()) {
            // @TODO: Update to specific exception after deciding the folder location of where that should live {JO: 2021-03-26}
            throw new SentryException('Deleting a user requires a valid user ID');
        }

        // the `wp_delete_user()` function is not available until the user administration API is loaded
        WordPressRepository::requireWordPressUserAdministrationAPI();

        if (! wp_delete_user($this->getId(), $reassignUserId)) {
            // @TODO: Update to specific exception after deciding the folder location of where that should live {JO: 2021-03-26}
            throw new SentryException('User could not be deleted');
        }

        return true;
    }

    /**
     * Saves the current user instance.
     *
     * @return self
     * @throws SentryException
     */
    public function save() : User
    {
        // @TODO: we should support additional fields in convertToSource in the future {FN: 2021-03-30}
        $id = wp_insert_user((new UserAdapter($this))->convertToSource());

        if ($id instanceof WP_Error) {
            // @TODO: Update to specific exception after deciding the folder location of where that should live {JO: 2021-03-26}
            throw new SentryException('Failed to save the User model.');
        }

        $this->setId($id);

        return $this;
    }

    /**
     * Seeds an instance of a User without saving,.
     *
     * @param array $data
     * @return User
     */
    public static function seed(array $data = []) : User
    {
        return (new User())->setProperties($data);
    }

    /**
     * Updates the given user instance.
     *
     * @return self
     * @throws SentryException
     */
    public function update() : User
    {
        return $this->save();
    }

    /**
     * Gets a User.
     *
     * @param int|string|null $identifier an ID, email, or handle
     * @return User|null user object, if found
     */
    public static function get($identifier)
    {
        /* @NOTE we expect to pass an integer to identify a user ID, a numerical string should be assumed to be a login handle only */
        if (is_int($identifier)) {
            return static::getById($identifier);
        }

        /* @NOTE this accounts for an email string used alternatively as a login handle */
        if (false !== filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return static::getByEmail($identifier) ?? static::getByHandle($identifier);
        }

        if (is_string($identifier)) {
            return static::getByHandle($identifier);
        }

        return null;
    }

    /**
     * Gets a User associated with a given email.
     *
     * @param string $email the email to search for
     * @return User|null
     */
    public static function getByEmail(string $email)
    {
        if ($user = get_user_by('email', $email)) {
            return static::seed(UserAdapter::getNewInstance($user)->convertFromSource());
        }

        return null;
    }

    /**
     * Gets a User associated with a given login handle.
     *
     * @param string $handle the login to search for
     * @return User|null
     */
    public static function getByHandle(string $handle)
    {
        if ($user = get_user_by('login', $handle)) {
            return static::seed(UserAdapter::getNewInstance($user)->convertFromSource());
        }

        return null;
    }

    /**
     * Gets a User associated with a given ID.
     *
     * @param int $id the id to search for
     * @return User|null
     */
    public static function getById(int $id)
    {
        if ($user = get_user_by('id', $id)) {
            return static::seed(UserAdapter::getNewInstance($user)->convertFromSource());
        }

        return null;
    }

    /**
     * Gets the currently logged-in user.
     *
     * @return User|null
     */
    public static function getCurrent()
    {
        $user = UserAdapter::getNewInstance(wp_get_current_user())->convertFromSource();

        if (ArrayHelper::get($user, 'id', 0) > 0) {
            return static::seed($user);
        }

        return null;
    }

    /**
     * Logs in the user.
     *
     * @return void
     * @throws UserLogInException
     */
    public function logIn() : void
    {
        WordPressRepository::logInUser($this);
    }

    /**
     * Determines whether the user is logged in.
     *
     * @return bool
     */
    public function isLoggedIn() : bool
    {
        if (! $currentUser = static::getCurrent()) {
            return false;
        }

        return $currentUser->id === $this->id;
    }

    /**
     * Determines if the user instance is registered in database.
     *
     * @return bool
     */
    public function isRegistered() : bool
    {
        if ($this->id > 0) {
            return username_exists($this->getHandle());
        }

        return false;
    }
}
