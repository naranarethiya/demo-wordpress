<?php

// Cleans up the environment accounting for the chroot, also defines Pagely constants

// detect chroot and if so fix DOCUMENT_ROOT
if (__DIR__ == '/pagely' || __DIR__ == '/godaddy' || __DIR__ == '/platform/misc') {
    if (is_link('/httpdocs')) {
        // In this scenario we are always double symlinked and the final symlink is absolute, so realpath is better than readlink
        $httpdocsReal = realpath('/httpdocs');
        if ($httpdocsReal !== false) {
            $_SERVER["DOCUMENT_ROOT"] = $httpdocsReal . '/';
        }
    } else {
        $_SERVER["DOCUMENT_ROOT"] = '/httpdocs/';
    }
}

define('PAGELYBIN', '/pagely');

// fix is_ssl detection
if (isset($_SERVER['HTTP_HTTPS'])) {
    $_SERVER['HTTPS'] = $_SERVER['HTTP_HTTPS'];
}

// wordpress config
define('AUTOMATIC_UPDATER_DISABLED', true);
define('DISABLE_WP_CRON', true);
define('AUTOSAVE_INTERVAL', 300);

if (!defined('WP_CRON_LOCK_TIMEOUT')) {
    define('WP_CRON_LOCK_TIMEOUT', 120);
}

// force disable timthumb webshot
define('WEBSHOT_ENABLED', false);

// force disable wordpress theme editing
define('DISALLOW_FILE_EDIT', true);

// PHP basic auth compat
if (!empty($_SERVER["REMOTE_AUTHORIZATION"])) {
    $d = base64_decode($_SERVER["REMOTE_AUTHORIZATION"]);
    if ($d !== false) {
        list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $d);
    }
}

// Authorization header compat
if (empty($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

// Set file permissions using umask
define('FS_CHMOD_DIR', (0777 & ~umask()));
define('FS_CHMOD_FILE', (0666 & ~umask()));

// Remove cruft headers that confuse some plugins like ithemes better security
// REMOTE_ADDR has the correct ip
unset($_SERVER['HTTP_X_CLUSTER_CLIENT']);
unset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']);
unset($_SERVER['HTTP_X_FORWARDED_FOR']);

// Before we include anything from the user, include our creds
if (file_exists(PAGELYBIN . '/config.php')) {
    include PAGELYBIN . '/config.php';
}

$user_setup = '/user/setup.php';
if (file_exists($user_setup)) {
    include $user_setup;
}
