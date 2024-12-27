<?php

//=====
// load wordpress
define('DISABLE_WP_CRON', true);

// define WP_CONTENT_DIR to be the current directory
// so no plugins or themes are loaded
define('WP_CONTENT_DIR', __DIR__);

$rootdir = $_SERVER['DOCUMENT_ROOT'];

// we're skipping wp-load.php, so define ABSPATH
define('ABSPATH', $rootdir . '/');

# load wordpress
require_once $rootdir . '/wp-config.php';
