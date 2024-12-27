<?php

/*
    Run cron notes:

    URL parameters:
        - hook (str) cron name
        - ts (int) cron timestamp
        - hash (str) cron argument hash (md5)
        - run_type (str) single|multi (Default: single)
        - run_limit (int) for type multi; limit the number of overdue crons to run


    There are 2 types of cron hooks in the wordpress system.
    Continuously-Scheduled & One-Time-Run

    Continuously-Scheduled:
        * will have a "schedule" attribute, type:string & not-empty
        * will have an "interval" attribute, type:int and greater than 0

    One-Time-Run:
        One-Time-Run can have multiple different states depending on the system
        that is inserting the cron (IE: Crontrol Plugin, WooCommerce, etc).
        State 1:
            * will have "schedule" attribute, type:string & empty
            * will not have "interval" attribute
        State 2:
            * will have a "schedule" attribute, type:bool value:false
            * will have an "interval" attribute, type:int value: 0


*/

require_once __DIR__ . '/auth.php';
define('DISABLE_WP_CRON', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';
require_once __DIR__ . '/mwp.includes.php';

//=====
// 
// check incoming query string has all required parameters
if (!isset($_GET['hook']) || !isset($_GET['ts']) || !isset($_GET['hash'])) {
    http_response_code(400);
    die("bad parameters");
}

// get a run type from the query string if present (single|multi) | default: single
$run_type = (isset($_GET['run_type'])) ? $_GET['run_type']: "single";
// get a run limit for multi run crons or set a default
$run_limit = (isset($_GET['run_limit'])) ? $_GET['run_limit']:8;
// run time limit is 5min - 15%
$run_time_limit = 300 - (300 * .15);


// extract cron vars
$hookname   = $_GET['hook'];
$timestamp  = (int)$_GET['ts'];
$hash       = $_GET['hash'];
$method     = $_SERVER['REQUEST_METHOD'];

// start measuring run time
$run_start = microtime(true);

// return payload
$payload = [
    'hook'   => $hookname,
    'ts'     => $timestamp,
    'hash'   => $hash,
    'result' => false,
    'run_count'=> 0,
];

if ($method === 'POST') {

    $payload['action'] = 'run';
    $run_count = 0;
    // cron run results
    $result = [
        'result'=>false,
        'crons'=>[]
    ];


    // run initial cron sent in
    $result = mwp_exec_cron($hookname, $timestamp, $hash);

    // check if we should try to execute more crons
    if ( $result['result'] && ($run_type == "multi") ) {
        // get crons that are ready to run
        $overdue = mwp_gather_overdue_hooks(time(), $result['crons']);

        foreach($overdue as $hookname=>$props) {
           if ( $run_count >= $run_limit || ((microtime(true) - $run_start) > $run_time_limit) ) {
               break;
           }

           $result = mwp_exec_cron($hookname, $props['timestamp'], $props['hash']);

           $run_count++;
        }
    }

    $payload['result'] = (isset($result['result'])) ? $result['result']:false;
    $payload['crons'] = (is_array($result['crons'])) ? $result['crons']:[];
    $payload['run_count'] = 1 + $run_count;


} else if ($method === 'DELETE') {

    $payload['action'] = 'delete';

    // delete cron
    $payload['result'] = wp_unschedule_event($timestamp, $hookname, $hook['args']);

    // fill response with the rest of the cron schedule
    $payload['crons'] = mwp_format_crons(get_option('cron'));
}

header("Cache-Control: no-cache");
header("Content-type: application/json");

echo json_encode($payload, JSON_PRETTY_PRINT);
