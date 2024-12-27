<?php

namespace GoDaddy\WordPress\MWC\Common\Migrations;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * The migration handler executes the pending migration routines and keeps track of their statuses.
 */
class MigrationHandler implements ComponentContract
{
    use CanGetNewInstanceTrait;
    use HasComponentsTrait;

    /** @var string the option key used to store information about routines that were executed. */
    protected const FINISHED_MIGRATION_ROUTINES_OPTION_NAME = 'mwc_finished_migration_routines';

    /** @var string the option key used to determine whether there are migration routines being executed or not. */
    protected const MIGRATION_ROUTINES_EXECUTION_LOCK_OPTION_NAME = 'mwc_migration_routines_execution_lock';

    /**
     * Initializes the component.
     */
    public function load() : void
    {
        $this->migrate();
    }

    /**
     * Stores information about a migration routine that was already executed.
     *
     * @param string $migrationRoutineClassName
     * @param string $status
     *
     * @return void
     */
    protected function addFinishedMigrationRoutine(string $migrationRoutineClassName, string $status) : void
    {
        try {
            $finishedRoutines = ArrayHelper::combine(
                $this->getFinishedMigrationRoutines(),
                [
                    $migrationRoutineClassName => [
                        'executedOn' => $this->getExecutedOnDateTime(),
                        'status'     => $status,
                    ],
                ]
            );
        } catch (BaseException $exception) {
            // ArrayHelper::combine() throws an exception if any of the parameters is not an array.
            // In this case we are always passing arrays so the exception shouldn't occur.
            $finishedRoutines = [];
        }

        update_option(static::FINISHED_MIGRATION_ROUTINES_OPTION_NAME, $finishedRoutines);
    }

    /**
     * Executes a migration routine.
     *
     * @param string $migrationRoutineClassName
     *
     * @return void
     */
    protected function executeMigrationRoutine(string $migrationRoutineClassName) : void
    {
        $status = 'success';

        try {
            if (! static::maybeLoadComponent($migrationRoutineClassName)) {
                return;
            }
        } catch (Exception $ex) {
            SentryException::getNewInstance("{$migrationRoutineClassName} errored", $ex);

            $status = 'error';
        }

        $this->addFinishedMigrationRoutine($migrationRoutineClassName, $status);
    }

    /**
     * Gets the formatted date and time to be used as the migration routine execution information.
     *
     * @return string
     */
    protected function getExecutedOnDateTime() : string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);
    }

    /**
     * Gets a list of migration routines that are already executed.
     *
     * @return array<string, mixed>
     */
    protected function getFinishedMigrationRoutines() : array
    {
        return ArrayHelper::wrap(get_option(static::FINISHED_MIGRATION_ROUTINES_OPTION_NAME, []));
    }

    /**
     * Gets a list of migration routines that are pending execution.
     *
     * @return string[]
     */
    protected function getMigrationRoutinesToBeExecuted() : array
    {
        $availableMigrationRoutines = ArrayHelper::wrap(Configuration::get('migrations.routines', []));

        return ArrayHelper::wrap(array_diff($availableMigrationRoutines, array_keys($this->getFinishedMigrationRoutines())));
    }

    /**
     * Determines whether there's a migration operation being executed.
     *
     * @return bool
     */
    protected function isLocked() : bool
    {
        return 'yes' === get_option(static::MIGRATION_ROUTINES_EXECUTION_LOCK_OPTION_NAME, 'no');
    }

    /**
     * Locks the migration operation, so we won't have more than one executing in parallel.
     *
     * {@see MigrationHandler::lock()}
     *
     * @return void
     */
    protected function lock() : void
    {
        update_option(static::MIGRATION_ROUTINES_EXECUTION_LOCK_OPTION_NAME, 'yes');
    }

    /**
     * Iterates over all the pending migration routines and execute them all.
     *
     * @return void
     */
    protected function migrate() : void
    {
        $migrationRoutineList = $this->getMigrationRoutinesToBeExecuted();

        if (! $migrationRoutineList || $this->isLocked()) {
            return;
        }

        $this->lock();

        foreach ($migrationRoutineList as $migrationRoutine) {
            $this->executeMigrationRoutine($migrationRoutine);
        }

        $this->unlock();
    }

    /**
     * Unlocks the migration operation.
     *
     * {@see MigrationHandler::lock()}
     *
     * @return void
     */
    protected function unlock() : void
    {
        update_option(static::MIGRATION_ROUTINES_EXECUTION_LOCK_OPTION_NAME, 'no');
    }
}
