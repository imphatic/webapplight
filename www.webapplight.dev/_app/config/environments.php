<?php
/*
    ----------------------------------------------------------------------------
    ENVIRONMENT CONFIGURATION
    This feature is useful when your application needs to work in multiple
    environments and you need different settings for each.

    For instance, when you have the production server and your local server and you
    need different database credentials for each.

    Basic Syntax:
    folder name => array(web address 1, web address 2, etc.)

    EXAMPLE USAGE:
    'local' => array('webappight.dev', 'www.webapplight.dev')

    In the folder /_app/config/local would be a file named database.php
    That document would have the code:

    <?php
    return array(
        'database' => array(
            'hostname'  => 'http.path-to-hostname.com'
    )
    );

    Now when you reach your application with the address webapplight.dev or www.webapplight.dev
    then your application will just change the setting of the hostname from the default
    (defined in /_app/config/database.php) to what is defined inside /_app/config/local/database.php

    You will notice that you do not need to redefine everything.  Just the changes that
    need to override the default configuration.                                      */


return array(
  'environments' => array(
        'dev' => array('www.webapplight.dev')
  )
);