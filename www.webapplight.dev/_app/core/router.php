<?php

class Route
{
    public static $rawRequest = null;
    public static $baseRequest = null;
    public static $extension = false;

    public static $userRoute;
    public static $userRouteFound = false;
    public static $userRouteParamsFound = false;
    public static $userRouteParams;

    public static function internalRouting()
    {
        self::parseRequest();

        $internalRoutes = array(
            '/sitemap.xml' => '/sitemap.php'
        );
        $request = self::$rawRequest;

        return (array_key_exists($request, $internalRoutes)) ? $internalRoutes[$request] : false ;

    }
    public static function parseRequest()
    {
        if(self::$rawRequest == null)
        {
            $raw = $_SERVER['REQUEST_URI'];
            $base = $raw;

            if(strpos($base, "?") !== false)
            {
                $base = substr($base, 0, strpos($base, "?"));
                $raw = substr($raw, 0, strpos($raw, "?"));
            }

            if(strpos($base, ".") !== false)
            {
                $base = substr($base, 0, strpos($base, "."));
                self::$extension = true;
            }

            if(substr($base, -1) == "/" && strlen($base) > 1)
            {
                $base = substr($base, 0, -1);
            }
            self::$rawRequest = $raw;
            self::$baseRequest = $base;
        }
    }

    public static function getFileToLoad()
    {
        // Check for an internal Route
        $internalRouting = self::internalRouting();
        if($internalRouting) return $internalRouting;

        if(!self::$userRouteFound) self::parseRequest();

        // Does the request end with .php?
        // Does the request + /index.php exist?
        // Does the request + .php exist?
        $physicalLocation = self::checkForPhysicalFile();
        if($physicalLocation) return $physicalLocation;

        // Check for a virtual page.
        $dynamicLocation = self::checkDynamicPages();
        if($dynamicLocation) return $dynamicLocation;

        // If none of the above, return 404 page.
        return "/404.php";
    }

    public static function checkForPhysicalFile($base = null)
    {
        $base = (!$base) ? self::$baseRequest : $base;

        if($base == "/" && file_exists("{$_SERVER['DOCUMENT_ROOT']}/index.php"))
        {
            return "/index.php";
        }

        if(self::$extension)
        {
            return $base;
        }

        if(file_exists("{$_SERVER['DOCUMENT_ROOT']}/". $base. "/index.php"))
        {
            return $base . "/index.php";
        }

        if(file_exists("{$_SERVER['DOCUMENT_ROOT']}/" . $base . ".php"))
        {
            return $base . ".php";
        }

        if(file_exists("{$_SERVER['DOCUMENT_ROOT']}/" . $base))
        {
            return $base;
        }

        return false;
    }

    public static function checkDynamicPages()
    {
        // Build your own virtual pages using the example below.
        return false;
        /*

        $pages = fw::select("SELECT * FROM pages WHERE url = '" . self::$baseRequest . "/'");
        if($pages) {
            return "/page.php";
        }

        return false;
        */
    }




    /*
    ----------------------------------------------------------------------------
    GENERATE URL POSSIBILITIES
    Generate all the possible ways that the user could have defined and targeted
    the current URL in the database including using a wildcard of *

    Return Example:
    Array
    (
        [0] => /this/is/something/
        [1] => this/is/something/
        [2] => /this/is/something
        [3] => this/is/something
        [4] => /this/*
        [5] => this/*
        [6] => /this/is/*
        [7] => this/is/*
        [8] => /this/is/something/*
        [9] => this/is/something/*
        [10] => '/*'
        [11] => '*'
    )
                                                                            */

    public static function generateUrlPossibilities ($uri=null)
    {
        $uri = ($uri) ? $uri :self::$rawRequest;
        $list[] = $uri;

        if($uri != "/")
        {
            $list[] = substr($uri,1);           // remove opening slash
            $list[] = substr($uri, 0, -1);      // remove ending slash
            $list[] =  trim($uri, '/');         // remove both slashes

            $uriParts = explode("/", $uri);
            $numOfParts = count($uriParts);
            $count = 1;
            $trail = "";
            foreach($uriParts as $part) {
                if($part != "") {
                    $slash = ($count == $numOfParts) ? "" : "/";
                    $star = ($count == $numOfParts) ? "" : "*";
                    $part .= $slash;
                    $list[] = "/" . $trail . $part . $star; // with opening slash
                    $list[] = $trail . $part . $star;       // without opening slash
                    $trail .= $part;
                }
                $count++;
            }
        }
        $list[] = "/*";
        $list[] = "*";

        return $list;
    }

    public static function group($uri, $callBack)
    {
        //Has a user route already been found?
        if(self::$userRouteFound)  return false;

        self::parseRequest();
        $uri = self::routeRefine($uri);

        if(substr(self::$baseRequest, 0, strlen($uri)) == $uri) {
            self::$baseRequest = substr(self::$baseRequest, strlen($uri));
            $callBack();
        }

    }

    public static function routeFound($location)
    {
        //make sure $uri starts with a slash
        if(substr($location, 0, 1) != '/' && strlen($location) > 1)  $location = '/' . $location;

        self::$userRouteFound = true;
        self::$baseRequest = $location;
    }

    public static function routeRefine($uri)
    {
        //remove trailing slash
        if(substr($uri, -1) == '/' && strlen($uri) > 1) $uri = substr($uri, 0, -1);

        //make sure $uri starts with a slash
        if(substr($uri, 0, 1) != '/' && strlen($uri) > 1)  $uri = '/' . $uri;

        //check for variables
        if(strpos($uri, "{") !== false || strpos($uri, "*") !== false) {
            self::$userRouteParamsFound = true;
        }

        return $uri;
    }

    public static function routeUserDefined($uri, $location, $type)
    {

        //Has a user route already been found?
        if(self::$userRouteFound)  return false;

        //Is the request the right type or an any?
        if(strtoupper($type) != $_SERVER['REQUEST_METHOD'] && strtoupper($type) != 'ANY')  return false;

        self::parseRequest();
        $uri = self::routeRefine($uri);

        if(self::$baseRequest == $uri) self::routeFound($location);

        if(self::$userRouteParamsFound)
        {
            $uriParts = explode("/", $uri);
            $baseRequestParts = explode("/", self::$baseRequest);

            if(count($uriParts) != count($baseRequestParts) && (strpos($uri, "*") === false)) return false;

            //if(count($uriParts) == 0) return false;

            foreach($uriParts as $key => $uriPart)
            {
                $baseRequestPart = (isset($baseRequestParts[$key])) ? $baseRequestParts[$key] : null;

                if($uriPart == $baseRequestPart)
                {
                    //Exact match
                } else if (strpos($uriPart, "{") !== false)
                {
                    //Variable found
                    if($baseRequestPart == "") return false;
                    $getVar = trim($uriPart, "{}");
                    $_GET[$getVar] = $baseRequestPart;
                } else if ($uriPart == "*")
                {
                    //Matches a star
                } else if(end($uriParts) == $uriPart && $uriPart == '*') {
                    //We are at the end and it is a star.
                    break;
                } else {
                    self::$userRouteParamsFound = false;
                    return false;
                }
            }
            self::routeFound($location);
        }

    }

    public static function any($uri, $location)
    {
        self::routeUserDefined($uri, $location, 'any');
    }

    public static function get($uri, $location)
    {
        self::routeUserDefined($uri, $location, 'get');
    }

    public static function post($uri, $location)
    {
        self::routeUserDefined($uri, $location, 'post');
    }

    public static function put($uri, $location)
    {
        self::routeUserDefined($uri, $location, 'put');
    }

    public static function delete($uri, $location)
    {
        self::routeUserDefined($uri, $location, 'delete');
    }

}


/*
 * Workflow Notes:
 * The purpose of this class is basically to decide what file should be loaded.  It won't actually load the file,
 * but rather it will return the path from the root to the file that should be loaded, including if that file
 * should be a 404.
 */