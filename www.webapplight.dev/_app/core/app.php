<?php
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;

class App
{
    private static $settingsFiles = array(
        'app.php',
        'database.php'
    );

    private static $settingsRootPath = '../config/';
    public static $settings = array();

    public static $loadedFile;

    static function loadAppSettings()
    {
        foreach(self::$settingsFiles as $file)
        {
            $data = include(self::$settingsRootPath . $file);
            self::$settings = array_merge(self::$settings, $data);
        }

        self::checkEnvironment();
    }

    static function get($var)
    {
        if(count(self::$settings) == 0)
        {
            self::loadAppSettings();
        }

        $return = null;

        if(strpos($var, ".") > 0)
        {
            $parts = explode(".", $var);
            $firstpass = true;

            foreach($parts as $part)
            {
                if($firstpass) {
                    $return = self::$settings[$part];
                    $firstpass = false;
                } else {
                    $return = $return[$part];
                }
            }
        } else {
            $return = self::$settings[$var];
        }

        return $return;
    }

    static function setupDebugHandeling()
    {
        //Check IP white list
        $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : null;
        $whiteList = self::get('debug.ip_white_list');

        if(is_array($whiteList) && in_array($ip, $whiteList))
        {
            self::$settings['debug']['set'] = true;
        }

    }

    static function checkEnvironment()
    {
        $environments = include('../config/environments.php');

        if(count($environments) > 0)
        {
            foreach($environments as $environment)
            {
                foreach($environment as $folder => $addresses)
                {
                    foreach($addresses as $webAddress) {
                        if($_SERVER['SERVER_NAME'] == $webAddress)
                        {
                            self::setEnvironment($folder, $webAddress);
                        }

                    }

                }
            }
        }

    }

    static function setEnvironment($folder, $webAddress)
    {
        if(!file_exists('../config/' . $folder))
        {
            trigger_error("Web App Light Setup Error: You have defined the environment $webAddress but the folder $folder does not exist inside /_app/config/", E_USER_ERROR);
        }
        foreach(self::$settingsFiles as $file)
        {
            $settingsFile = self::$settingsRootPath . $folder . '/' . $file;
            if(file_exists($settingsFile)) {
                $data = include($settingsFile);
                self::$settings = array_replace_recursive(self::$settings, $data);
            }
        }

    }

    /*
    ----------------------------------------------------------------------------
    PSR-0 AUTOLOADER
    The next 3 functions are all used by the PSR-0 standard autoloader.
    For more information: http://www.php-fig.org/psr/psr-0/                   */

    public static function autoloaderPSR0 ($className)
    {
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        return $fileName;
    }

    public static function vendorLocations ($className)
    {
        $vendors = array(
            '../modules/',
        );

        foreach($vendors as $vendor)
        {
            if(file_exists($vendor . $className))
            {
                $location = $vendor;
                break;
            }
        }

        return (isset($location)) ? $location : '' ;
    }

    public static function autoloader($className)
    {
        // Check in settings autoload map for match in aliases
        $map = self::get('aliases');
        $className = (array_key_exists($className, $map)) ? $map[$className] : $className;

        $className = self::autoloaderPSR0($className);
        $location = self::vendorLocations($className);

        $file = $location . $className;

        // Include the file
        if(file_exists($file))
        {
          include($file);
        } else
        {
          trigger_error('The class that you are requesting was not found by the autoloader.
                        It attempted to find: ' . $file);
        }

    }

    /*
    ----------------------------------------------------------------------------
    ERROR HANDLING
    How the app will handle errors in your code.                               */
    public static function setupErrorHandling()
    {
        if(self::get('debug.set'))
        {
            $run     = new Whoops\Run;
            $handler = new PrettyPageHandler;
            $handler->setPageTitle("Whoops! There was a problem.");

            $requestMethod = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : null;

            if($requestMethod == 'XMLHttpRequest')
            {
                $run->pushHandler(new JsonResponseHandler);
            } else {
                $run->pushHandler($handler);
            }
            $run->register();
        } else {
            register_shutdown_function("App::productionErrorHandling");
        }
    }

    public static function productionErrorHandling()
    {
        $error = error_get_last();

        if( $error !== NULL && $error['type'] < 8)
        {
            echo "<h1>Error:</h1><p>We have encountered an error while attempting to process your request.</p>";

        }
    }

    public static function handle404()
    {
        //record stats
        if(!isset($_GET['nostats']))
        {
            $url = $_SERVER['REQUEST_URI'];
            $parts = explode("?", $url);

            $url_request  = $parts[0];
            $referer      = (isset($_SERVER['HTTP_REFERER']))    ? $_SERVER['HTTP_REFERER']    : '';
            $query_string = (isset($_SERVER['QUERY_STRING']))    ? $_SERVER['QUERY_STRING']    : '';
            $user_agent   = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $remote_addr  = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
            $post_data    = (isset($_POST)) ? serialize($_POST) : '';

            fw::prep('_404_stats:url_requested,referral_source,remote_addr,query_string,post_data,http_user_agent',
                "text:$url_request",
                "text:$referer",
                "text:$remote_addr",
                "text:$query_string",
                "text:$post_data",
                "text:$user_agent")->insert();
        }

        header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
        $fourohfour = self::get('root') . "/404.php";
        require($fourohfour);
    }

    public static function record301Stats($request, $id=0)
    {
        $referer      = (isset($_SERVER['HTTP_REFERER']))    ? $_SERVER['HTTP_REFERER']    : '';
        $query_string = (isset($_SERVER['QUERY_STRING']))    ? $_SERVER['QUERY_STRING']    : '';
        $user_agent   = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';

        fw::prep('_301_stats:301_id,url_requested,referral_source,query_string,http_user_agent',
            "int:{$id}",
            "text:$request",
            "text:$referer",
            "text:$query_string",
            "text:$user_agent")->insert();
    }

    public static function checkFor301 ()
    {
        Route::parseRequest();

        $rawList = Route::generateUrlPossibilities();

        foreach($rawList as $url)
        {
            $prepedList[] = 'text:'. $url;
            $ques[] = '?';
        }
        $questions = implode(',', $ques);

        $prepedList = array_merge($prepedList, $prepedList);

        $check = fw::prep('SELECT * FROM _301 WHERE redirect_from IN ('.$questions.')
                           AND (`active` = 1
                           AND (date_enable < CURRENT_TIMESTAMP OR date_enable IS NULL)
                           AND (date_expire > CURRENT_TIMESTAMP OR date_expire IS NULL))
                           ORDER BY FIELD(redirect_from, '.$questions.')', $prepedList)->select(1);

        if($check)
        {
            //record stats
            self::record301Stats(Route::$rawRequest, $check->id);

            $redirect = $check->redirect_to;

            if(substr($redirect, 0, 7) != 'http://' && substr($redirect, 0, 8) != 'https://')
            {
                //make sure $redirect starts with a slash
                if(substr($redirect, 0, 1) != '/')  $redirect = '/' . $redirect;

                //make sure $redirect ends with a slash
                if(substr($redirect, -1) != '/') $redirect .= "/";
            }

            //do the redirection
            header("Location: " . $redirect, true, $check->type);
            exit();
        }
    }


    public static function loadPage()
    {
        Meta::setupPageMetaVariables();

        if(self::$loadedFile == "/404.php")
        {
            self::handle404();
        } else  {
            require(self::get('root') . self::$loadedFile);
        }

    }




}

