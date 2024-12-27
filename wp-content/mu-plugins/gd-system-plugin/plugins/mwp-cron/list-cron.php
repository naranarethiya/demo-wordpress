<?php


require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/load.php';
require_once __DIR__ . '/mwp.includes.php';


//=====
// 

$site_url = get_option('siteurl');

$crons = get_option('cron');

$payload = [
    'siteUrl' => $site_url,
    'db' => [
        'host' => DB_HOST,
        'name' => DB_NAME,
    ],
    'crons' => mwp_format_crons($crons),
];

header("Cache-Control: no-cache");
header("Content-type: application/json");

echo json_encode($payload, JSON_PRETTY_PRINT);
