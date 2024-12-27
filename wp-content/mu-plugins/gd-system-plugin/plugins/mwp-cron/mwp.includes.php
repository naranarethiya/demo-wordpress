<?php

/**
 * Max number of cron hooks to accept
 */
define("MWP_CRON_MAX_HOOKS", 50);

/**
 * comma separated string of blacklisted crons
 * NOTE: csv will be exploded into an array in mwp_format_crons().
 * 		 we want these in a constant to prevent overriding of these values
 */
define("MWP_BLACKLIST_CRONS", "");

/**
 * https://developer.wordpress.org/reference/functions/do_action_ref_array/#source
 */
function mwp_do_action_direct($hookname, $args = [])
{
    // From: https://github.com/wp-cli/cron-command/blob/f4e1d02642fc56d84504dd012aeb64adee0aae8c/src/Cron_Event_Command.php#L330-L332
    if ( ! defined( 'DOING_CRON' ) ) {
        define( 'DOING_CRON', true );
    }
    do_action_ref_array($hookname, $args);
}

/**
 * Flatten wp_options['cron'] array of scheduled crons into
 * a single dimension array.
 * Also filter out crons deemed invalid or permanently blacklisted.
 *
 * @param $cron_option list of crons returned from wp function get_option('cron')
 * @return array list of crons formatted as a single-dimension array
 */
function mwp_format_crons($cron_option = [])
{

    $crons = [];

    // build blacklist array from csv
    $blacklist = explode(",", MWP_BLACKLIST_CRONS);
    // trim any leading/trailing whitespace
    foreach ($blacklist as $idx => $cron) {
        $blacklist[$idx] = trim($cron);
    }

    foreach ($cron_option as $timestamp => $cronhooks) {

        if (!is_int($timestamp)) {
            continue;
        }

        foreach ($cronhooks as $hookname => $keys) {

            if (!is_string($hookname)) {
                continue;
            }

            // check blacklist
            if (in_array($hookname, $blacklist)) {
                continue;
            }

            foreach ($keys as $hash => $hook) {
                if (!is_string($hash)) {
                    continue;
                }

                // handle schedule (bool)
                // if schedule is a bool(false) this is a one time run cron, clear it
                if (is_bool($hook['schedule'])) {
                    $hook['schedule'] = "";
                }

                // check for cron interval's under 60sec.
                // NOTE: single run crons have interval=0 & schedule="" (IE: empty string)
                if (isset($hook['interval']) && $hook['interval'] < 60 && strlen($hook['schedule']) > 0) {
                    continue;
                }

                $hook['nextRun'] = (int)$timestamp;
                $hook['name'] = $hookname;
                $hook['hash'] = $hash;

                /*
                // TODO maybe sanity check number of crons (if there are more than X, return error)
                if ( count($crons) > (int)constant(MWP_CRON_MAX_HOOKS) ) {

                }	
                 */

                $crons[] = $hook;
            }
        }
    }

    return $crons;
}

/** run the given cron and return the result and updated list of crons
 * formatted with mwp_format_crons function
 *
 * @param str $hookname hookname to run
 * @param int $timestamp timestamp to run
 * @param str $hash argument hash to run
 * @return array [result: (bool), crons: (array)]
 *
 *
 */
function mwp_exec_cron($hookname, $timestamp, $hash) {

    // get crons from the DB
    $crons = get_option('cron');

    // build a result to return
    $result = [
        'result'=>false,
        'crons'=>NULL,
    ];

    // check incoming hook is in crons list
    if (!isset($crons[$timestamp][$hookname][$hash])) {
        http_response_code(404);
        die("target not found | ${hookname} - ${timestamp} - ${hash}");
    }

    $hook = $crons[$timestamp][$hookname][$hash];

    // run cron without $wp_filter[all]
    mwp_do_action_direct($hookname, $hook['args']);

    // delete the cron we just ran.
    // If a future run is to be scheduled, we'll use info in memory ($hook)
    // We store the removal of the cron as our result, in-case this is a one-time-run
    // and no further updates are required.
    $del_event = wp_unschedule_event($timestamp, $hookname, $hook['args']);
    if (is_bool($del_event)) { // check if return is bool. WP_Error can be returned for errors
        $result['result'] = $del_event;
    } else { // if bool not returned, result is false
        $result['result'] = false;
    }

    // re-query crons so we have the latest object graph
    $crons = get_option('cron');

    // GOTCHA NOTE:
    // if we received a $payload['result'] = false, this may not be technically correct,
    // some woocommerce events such as `woocommerce_cancel_unpaid_orders` is a
    // non-repeating event, however, as part of it's execution, it re-schedules
    // itself to run hourly (without an interval), this would cause the above
    // wp_unschedule_event() to return a `false` due to the timestamp not matching
    // the now re-scheduled event in `get_option('cron')`.
    // https://github.com/woocommerce/woocommerce/blob/313d40d3960da3de560a96566491bb6115141eec/plugins/woocommerce/includes/wc-order-functions.php#L910
    //
    // To factor in this edge-case, we will check our freshly queried $crons list
    // for the event that was to be deleted if our $payload['result'] = false, if the
    // event is not present, we will revert to $payload['result'] = true as the event
    // no longer exists under its executed parameters (just as if it was successfully un-scheduled).
    if ($result['result'] == false) {
        if (!isset($crons[$timestamp][$hookname][$hash])) {
            // cron not found under its executed parameters,
            // treat the previous `wp_unschedule_event` as successful
            $result['result'] = true;
        }
    }


    // check if the hook that executed is eligible for rescheduling
    if (is_string($hook['schedule']) && !empty($hook['schedule']) && isset($hook['interval']) && is_int($hook['interval'])) {

        // enforce min interval
        $interval = $hook['interval'];
        if ($interval < 60) {
            $interval = 60;
        }

        // update timestamp + interval for next run
        // $nextRun = $timestamp + $interval;
        // update using now() vs incoming timestamp
        $nextRun = time() + $interval;


        // add the cron at its next run
        $crons[$nextRun][$hookname][$hash] = $hook;

        // ensure crons are sorted by timestamp
        uksort($crons, 'strnatcasecmp');

        // overwrite 'cron' option with the modified/rescheduled cron object graph
        $result['result'] = update_option('cron', $crons);
    }

    // add formatted crons list to result
    $result['crons'] = mwp_format_crons($crons);

    return $result;

}

/**
 * create array of overdue crons
 * @param $now unixtimestamp now time scope as unix-timestamp
 * @param $formatted_crons list of crons formatted by mwp_format_crons function
 * @return array multi-dimensional array of hooks that can run
 * [
 *  {$hookname(str) name of cron to run}=> [
 *     'timestamp'=>(int) run timestamp,
 *     'hash'=>(str) arg hash
 *  ]
 * ]
 */
function mwp_gather_overdue_hooks($now, $formatted_crons) {
    $overdue = [];

    foreach($formatted_crons as $k => $v) {
       if ($v['nextRun'] < $now) {
        $overdue[$v['name']] = [
            'timestamp' => $v['nextRun'],
            'hash' => $v['hash']
        ];
       }
    }

    return $overdue;
}


















