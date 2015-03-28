<?php

    /*
    ----------------------------------------------------------------------------
    REGISTER AUTOLOADER
    Automatically loads requested classes from the vendor folders.            */

    spl_autoload_register(array('App', 'autoloader'));

    /*
    ----------------------------------------------------------------------------
    SETUP DEBUG HANDLING
    Manages the debug environment.                                            */

    App::setupDebugHandeling();


    /*
    ----------------------------------------------------------------------------
    REGISTER ERROR HANDLING
    Handle errors depending on if we are in debug mode or not.                */

    App::setupErrorHandling();


    /*
    ----------------------------------------------------------------------------
    SET TIME ZONE
    Avoids an annoying notice when your application uses a date related function
                                                                              */
    date_default_timezone_set(App::get('timezone'));


    /*
    ----------------------------------------------------------------------------
    CHECK AND HANDLE 301 REDIRECTS
    These are managed via the database.  Functionality is apparent from looking
    at the structure of the _301 table.                                       */

    App::checkFor301();


    /*
    ----------------------------------------------------------------------------
    ROUTE THE REQUEST
    Use the Router to figure out which file we should include or handle 404
    if no file is found.                                                      */

    require('../routes.php');
    App::$loadedFile =  Route::getFileToLoad();


    /*
    ----------------------------------------------------------------------------
    LOAD THE PAGE
    Load the page including meta data and setup page variables.
                                                                              */

    App::loadPage();




