<?php

return array(

    /*
    ----------------------------------------------------------------------------
    DEBUG SETTINGS
    use set to apply a blanket debug state.  Add your IP to the debug_ip_white_list
    and only your machine will see the app in debug state.                    */

    'debug' => array(
        'set' => true,
        'ip_white_list' => array(
            '191.128.52.0'
        )
    ),

    'timezone' => 'America/Chicago',

    /*
    ----------------------------------------------------------------------------
    CLASS ALIASES
    Aliases map used by the Autoloader.                                       */

    'aliases' => array(
        'fw'         => 'framework\framework',
    ),


    /*
    ----------------------------------------------------------------------------
    ENVIRONMENT VARIABLES
    The defaults are probably fine, override them if you need to.             */

    'root'      => $_SERVER['DOCUMENT_ROOT'],
    'app_root'  => $_SERVER['DOCUMENT_ROOT'] . '/_app',

    // should not end with a slash
    'url'       => (isset($_SERVER['HTTPS'])) ? 'https://' . $_SERVER['SERVER_NAME'] . '/' : 'http://' . $_SERVER['SERVER_NAME'],

    /*
    ----------------------------------------------------------------------------
    Web App Light APP SETTINGS
    Settings about Web App Light                                              */

    'version' => '1.0'
);