<?php

return [

    /*
     *--------------------------------------------------------------------------
     * Information related to the dashboard messages
     *--------------------------------------------------------------------------
     */
    'api' => [
        'url' => defined('MWC_DASHBOARD_MESSAGES_API_URL') ? MWC_DASHBOARD_MESSAGES_API_URL : 'https://api-events.mwc.secureserver.net/graphql',
    ],
    // whether users are opted in by default or not (merchants are opted out by default)
    'optedInByDefault' => false,
];
