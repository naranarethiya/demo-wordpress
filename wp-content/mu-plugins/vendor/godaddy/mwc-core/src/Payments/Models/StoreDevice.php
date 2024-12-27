<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Core\Features\Worldpay\Worldpay;

/**
 * Sets up a generic class for defining store devices external to WooCommerce.
 */
class StoreDevice extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /** @var string store device activated status */
    const STATUS_ACTIVATED = 'ACTIVATED';

    /** @var string Poynt Terminal Serial begins with */
    public const POYNT_SMART_TERMINAL_SERIAL_BEGINS = 'P6';

    /** @var string Poynt C Terminal Serial begins with */
    public const POYNT_C_TERMINAL_SERIAL_BEGINS = 'ST3';

    /** @var string Newland Terminal Serial begins with */
    public const NEWLAND_TERMINAL_SERIAL_BEGINS = 'N7';

    /** @var string VT Terminal Serial begins with */
    public const VT_TERMINAL_SERIAL_BEGINS = 'VT';

    /** @var string store device type - terminal */
    const TYPE_TERMINAL = 'TERMINAL';

    /** @var ?string|int the device model */
    public $id = null;

    /** @var ?string the name of the device */
    public $name = null;

    /** @var ?string the serial number of the device */
    public $serialNumber = null;

    /** @var ?string the current status of the device */
    public $status = null;

    /** @var ?string the id of the store this device is associated with - @TODO: consider replacing with a true relationship when/if there is a Store Model */
    public $storeId = null;

    /** @var ?string the device type */
    public $type = null;

    /**
     * @param array|null $bulkProperties mass assignment properties
     */
    public function __construct(array $bulkProperties = null)
    {
        if ($bulkProperties) {
            $this->setProperties($bulkProperties);
        }
    }

    /**
     * Gets the device model.
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the device name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the device serial number.
     *
     * @return string|null
     */
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    /**
     * Sets the device status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Gets the store the device belongs to.
     *
     * @return string|null
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Gets the device type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Determines if the device has been activated.
     *
     * @return bool
     * @throws Exception
     */
    public function isActivated() : bool
    {
        return $this->status === static::STATUS_ACTIVATED;
    }

    /**
     * Determines if the device is a Poynt smart terminal and is active.
     *
     * @return bool
     * @throws Exception
     */
    public function isActivePoyntSmartTerminal() : bool
    {
        return $this->isActivated() && $this->isPoyntSmartTerminal();
    }

    /**
     * Determines if the device is a Poynt smart terminal.
     *
     * @return bool
     * @throws Exception
     */
    public function isPoyntSmartTerminal() : bool
    {
        return $this->type === static::TYPE_TERMINAL && ! empty(
            ArrayHelper::where(
                $this->getSupportedSerialPrefixes(),
                function ($prefix) {
                    return StringHelper::startsWith($this->getSerialNumber() ?? '', $prefix);
                }
            )
        );
    }

    /**
     * Gets the supported serial prefixes.
     *
     * @return string[]
     * @throws Exception
     */
    protected function getSupportedSerialPrefixes() : array
    {
        $prefixes = [
            static::POYNT_SMART_TERMINAL_SERIAL_BEGINS,
            static::POYNT_C_TERMINAL_SERIAL_BEGINS,
        ];

        if (Worldpay::shouldLoad()) {
            $prefixes = ArrayHelper::combine($prefixes, [
                static::NEWLAND_TERMINAL_SERIAL_BEGINS,
            ]);
        }

        return TypeHelper::arrayOfStrings($prefixes, false);
    }

    /**
     * Sets the device model.
     *
     * @param string|null $identifier
     * @return StoreDevice
     */
    public function setId(string $identifier = null) : StoreDevice
    {
        $this->id = $identifier;

        return $this;
    }

    /**
     * Sets the device name.
     *
     * @param string|null $name
     * @return StoreDevice
     */
    public function setName(string $name = null) : StoreDevice
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the device serial number.
     *
     * @param string|null $serial
     * @return StoreDevice
     */
    public function setSerialNumber(string $serial = null) : StoreDevice
    {
        $this->serialNumber = $serial;

        return $this;
    }

    /**
     * Sets the device status.
     *
     * @param string|null $status
     * @return StoreDevice
     */
    public function setStatus(string $status = null) : StoreDevice
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Sets the store the device belongs to.
     *
     * @param string|null $store
     * @return StoreDevice
     */
    public function setStoreId(string $store = null) : StoreDevice
    {
        $this->storeId = $store;

        return $this;
    }

    /**
     * Sets the device type.
     *
     * @param string|null $type
     * @return StoreDevice
     */
    public function setType(string $type = null) : StoreDevice
    {
        $this->type = $type;

        return $this;
    }
}
