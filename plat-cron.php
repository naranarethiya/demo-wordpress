<?php
/**
 * A pseudo-cron daemon for scheduling WordPress tasks.
 *
 * Patched version of wp-cron.php, that allows for returning a list of scheduled events
 *
 * @package WordPress
 */


// disable any error output
define('WP_DEBUG', false);
ini_set('display_errors', 0);
// Turn off caching Comet/WP Super cache
define('DONOTCACHEPAGE', false);

class CronRunner
{
    private $actionSchedulerSupport = false;
    private $metrics = [];
    private $doingWpCron = "";

    // we won't start running another cron event once we have gone over TIME_LIMIT
    public const TIME_LIMIT = 60;

    public function __construct(bool $actionSchedulerSupport)
    {
        $this->actionSchedulerSupport = $actionSchedulerSupport;
    }

    private function startOutput(): void
    {
        if (!headers_sent()) {
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
            header('Cache-Control: no-cache, must-revalidate, max-age=0');
            header('Content-Type: text/event-stream');
        }
    }

    private function sendEvent(string $type, array $data): void
    {
        $marker = base64_encode(random_bytes(8));
        echo ">>> $marker\n";
        echo json_encode([
          'type' => $type,
          'time' => date('Y-m-d\TH:i:s+00:00'),
          'data' => $data,
        ]);
        echo "\n<<< $marker\n";
        flush();
    }


    public function run(): void
    {
        ignore_user_abort(true);
        $this->startOutput();

        /**
         * Tell WordPress the cron task is running.
         *
         * @var bool
         */
        define('DOING_CRON', true);

        if (! defined('ABSPATH')) {
            /** Set up WordPress environment */
            require_once __DIR__ . '/wp-load.php';
        }

        // Attempt to raise the PHP memory limit for cron event processing.
        wp_raise_memory_limit('cron');

        $crons = _get_cron_array();
        $schedule = $this->buildCronSchedule($crons);
        $this->sendEvent("cron-schedule", $schedule);

        if (!$this->grabCronLock()) {
            return;
        }

        $start = microtime(true);
        $this->sendEvent("start", []);
        $ran = $this->runCrons($crons);

        $crons = _get_cron_array();
        $schedule = $this->buildCronSchedule($crons);
        $this->sendEvent("cron-schedule", $schedule);
        $this->sendEvent("end", [
          'duration' => round(microtime(true) - $start, 3) * 1000,
          'events' => $ran
        ]);

        $this->releaseCronLock();
    }

    private function grabCronLock(): string
    {
        $gmt_time = microtime(true);

        // The cron lock: a unix timestamp from when the cron was spawned.
        $doing_cron_transient = get_transient('doing_cron');

        // Use global $doing_wp_cron lock, otherwise use the GET lock. If no lock, try to grab a new lock.
        if (empty($doing_wp_cron)) {
            // Called from external script/job. Try setting a lock.
            if ($doing_cron_transient && ($doing_cron_transient + WP_CRON_LOCK_TIMEOUT > $gmt_time)) {
                $this->sendEvent(
                    "cron-lock-failed",
                    [
                            'error' => "Failed to acquire lock. Another cron process is already running.",
                            'doing_cron_transient' => $doing_cron_transient,
                            'WP_CRON_LOCK_TIMEOUT' => WP_CRON_LOCK_TIMEOUT,
                            'gmt_time' => $gmt_time,
                    ]
                );
            }
            $doing_wp_cron        = sprintf('%.22F', microtime(true));
            $doing_cron_transient = $doing_wp_cron;
            set_transient('doing_cron', $doing_wp_cron);
        }

        /*
         * The cron lock (a unix timestamp set when the cron was spawned),
         * must match $doing_wp_cron (the "key").
         */
        if ($doing_cron_transient !== $doing_wp_cron) {
            $this->sendEvent(
                "cron-lock-failed",
                [
                  'error' => "Failed to acquire lock. Another cron process is already running.",
                  'doing_cron_transient' => $doing_cron_transient,
                  'doing_wp_cron' => $doing_wp_cron,
                ]
            );
        }
        $this->doingWpCron = $doing_wp_cron;

        return $doing_wp_cron;
    }

    private function releaseCronLock()
    {
        if (_get_cron_lock() === $this->doingWpCron) {
            delete_transient('doing_cron');
        }
    }

    private function buildCronSchedule(array $crons)
    {
        $now = time();
        $out = [];
        foreach ($crons as $time => $hooks) {
            foreach ($hooks as $hook => $hook_events) {
                foreach ($hook_events as $sig => $data) {

                    if ($this->actionSchedulerSupport && $hook == 'action_scheduler_run_queue') {
                        $out[] = $this->getActionSchedulerEvent();
                        continue;
                    }
                    $out[] = (object) array(
                        'hook'     => $hook,
                        'next_run_gmt'     => gmdate('c', $time),
                    );

                }
            }
        }
        return $out;
    }

    private function getActionSchedulerEvent()
    {
        $store = ActionScheduler::store();
        $pending = $store->query_actions([
            'status' => [ActionScheduler_Store::STATUS_PENDING],
            'orderby' => 'date',
            'order' => 'ASC',
        ]);
        if (!empty($pending)) {
            $next = $store->fetch_action($pending[0])->get_schedule()->get_date()->getTimestamp();
        } else {
            $next = strtotime('+1 hour');
        }

        $now = time();
        return [
            'hook' => 'action_scheduler_run_queue',
            'next_run_gmt' => gmdate('c', $next),
        ];
    }

    private function runCrons(array $crons): int
    {
        $gmt_time = microtime(true);
        $ran = 0;
        foreach ($crons as $timestamp => $cronhooks) {

            if ($timestamp > $gmt_time) {
                break;
            }

            foreach ($cronhooks as $hook => $keys) {

                foreach ($keys as $k => $v) {
                    $this->sendEvent("event-start", [
                      'hook' => $hook,
                      'lateness' => round($gmt_time - $timestamp, 3) * 1000,
                    ]);
                    $start = microtime(true);

                    $schedule = $v['schedule'];
                    $ran++;

                    if ($schedule) {
                        $result = wp_reschedule_event($timestamp, $schedule, $hook, $v['args'], true);

                        if (is_wp_error($result)) {
                            error_log(
                                sprintf(
                                    /* translators: 1: Hook name, 2: Error code, 3: Error message, 4: Event data. */
                                    __('Cron reschedule event error for hook: %1$s, Error code: %2$s, Error message: %3$s, Data: %4$s'),
                                    $hook,
                                    $result->get_error_code(),
                                    $result->get_error_message(),
                                    wp_json_encode($v)
                                )
                            );

                            /**
                             * Fires when an error happens rescheduling a cron event.
                             *
                             * @since 6.1.0
                             *
                             * @param WP_Error $result The WP_Error object.
                             * @param string   $hook   Action hook to execute when the event is run.
                             * @param array    $v      Event data.
                             */
                            do_action('cron_reschedule_event_error', $result, $hook, $v);
                        }
                    }

                    $result = wp_unschedule_event($timestamp, $hook, $v['args'], true);

                    if (is_wp_error($result)) {
                        error_log(
                            sprintf(
                                /* translators: 1: Hook name, 2: Error code, 3: Error message, 4: Event data. */
                                __('Cron unschedule event error for hook: %1$s, Error code: %2$s, Error message: %3$s, Data: %4$s'),
                                $hook,
                                $result->get_error_code(),
                                $result->get_error_message(),
                                wp_json_encode($v)
                            )
                        );

                        /**
                         * Fires when an error happens unscheduling a cron event.
                         *
                         * @since 6.1.0
                         *
                         * @param WP_Error $result The WP_Error object.
                         * @param string   $hook   Action hook to execute when the event is run.
                         * @param array    $v      Event data.
                         */
                        do_action('cron_unschedule_event_error', $result, $hook, $v);
                    }

                    // use alternate action hook scheduler if enabled
                    if ($this->actionSchedulerSupport && $hook == 'action_scheduler_run_queue') {
                        $this->sendEvent("event-end", [
                          'hook' => 'action_scheduler_run_queue',
                          'duration' => round(microtime(true) - $start, 3) * 1000,
                          'optimized' => true,
                        ]);
                        $this->runActionScheduler();
                    } else {
                        /**
                        * Fires scheduled events.
                        *
                        * @ignore
                        * @since 2.1.0
                        *
                        * @param string $hook Name of the hook that was scheduled to be fired.
                        * @param array  $args The arguments to be passed to the hook.
                        */
                        do_action_ref_array($hook, $v['args']);

                        $this->sendEvent("event-end", [
                          'hook' => $hook,
                          'duration' => round(microtime(true) - $start, 3) * 1000,
                        ]);
                    }

                    // If the hook ran too long and another cron process stole the lock, quit.
                    if (_get_cron_lock() !== $this->doingWpCron) {
                        return $ran;
                    }

                    // if the cron ran from over self::TIME_LIMIT quit
                    if (microtime(true) - $gmt_time > self::TIME_LIMIT) {
                        return $ran;
                    }
                }
            }
        }
        return $ran;
    }

    private function runActionScheduler()
    {
        $start = microtime(true);

        $runner = ActionScheduler::runner();
        $store = ActionScheduler::store();

        if ($store->has_pending_actions_due()) {
            $actions = [];
            $starts = [];
            add_action('action_scheduler_begin_execute', function ($action_id, $context) use ($store, &$actions, &$starts) {
                $actions[$action_id] = $store->fetch_action($action_id);
                $starts[$action_id] = $now;
                $this->sendEvent("event-start", [
                    'hook' => $actions[$action_id]->get_hook(),
                    'lateness' => round(microtime(true) - $actions[$action_id]->get_schedule()->get_date()->getTimestamp(), 3) * 1000,
                ]);
            }, 10, 2);
            add_action('action_scheduler_failed_execution', function ($action_id, $error, $context) use (&$actions, &$starts) {
                $start = $starts[$action_id];
                $this->sendEvent("event-end", [
                    'hook' => $actions[$action_id]->get_hook(),
                    'duration' => round(microtime(true) - $start, 3) * 1000,
                ]);
            }, 10, 3);
            add_action('action_scheduler_after_execute', function ($action_id, $action, $context) use (&$actions, &$starts) {
                $start = $starts[$action_id];

                $this->sendEvent("event-end", [
                    'hook' => $actions[$action_id]->get_hook(),
                    'duration' => round(microtime(true) - $start, 3) * 1000,
                ]);
            }, 10, 3);

            $count = $runner->run();
        }
        //$out['action_scheduler_status'] = $store->action_counts();
    }
}

$actionSchedulerSupport = class_exists('ActionScheduler_QueueRunner') && !empty($_GET['action_scheduler_support']);
$cronRunner = new CronRunner($actionSchedulerSupport);
$cronRunner->run();


/**
 * Retrieves the cron lock.
 *
 * Returns the uncached `doing_cron` transient.
 *
 * @ignore
 * @since 3.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return string|int|false Value of the `doing_cron` transient, 0|false otherwise.
 */
function _get_cron_lock()
{
    global $wpdb;

    $value = 0;
    if (wp_using_ext_object_cache()) {
        /*
         * Skip local cache and force re-fetch of doing_cron transient
         * in case another process updated the cache.
         */
        $value = wp_cache_get('doing_cron', 'transient', true);
    } else {
        $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", '_transient_doing_cron'));
        if (is_object($row)) {
            $value = $row->option_value;
        }
    }

    return $value;
}
