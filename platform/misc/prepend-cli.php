<?php

define('PAGELYBIN', '/pagely');

// Before we include anything from the user, include our creds
if (file_exists(PAGELYBIN . '/config.php')) {
    include PAGELYBIN . '/config.php';
}
