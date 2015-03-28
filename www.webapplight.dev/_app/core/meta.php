<?php
/*  ----------------------------------------------------------------------------
    META
    This document really does not need to be edited, but this is a good place
    to document a few important features.  Meta is intended to be used as a way
    to manage meta data (title, description, etc.) across the entire site.


    Database Notes:
    The "url" field accepts wildcards like "/products/*" and any page that starts
    with products will also have its meta data set to the data in that row.  What if
    you have "/products/*" and "/products/my-thing/" well then the rule of inheritance
    will apply.  So if "/products/my-thing" does not have a title set (or is blank) and
    "/products/*" does, then the title will be used from "/products/*".   This can go
    infinite levels deep too.

    The columns that end with "_append" tell Meta to just append the value to any data
    that is found up the chain.  So if "/products/*" title tag was "Products - " and
    "/products/my-thing/" title tag as "My Thing" and the column "title_append" existed
    and was set to 1, then the resulting title tag would be "Products - My Thing"


    Meta Variables:
    You can pass variables that are defined on the page to meta using:

    Meta::variables(array(
        'category' => 'Hand Towels',
        'name'     => 'john smith'
    ));

    Then in the database you can add to any meta tag {{category}} or {{name}}

*/


class Meta {

    public static $metaArray = null;
    public static $variables = null;

    public static function setupPageMetaVariables()
    {
        $rawList = Route::generateUrlPossibilities();

        if(App::$loadedFile == "/404.php")
        {
            $rawList = array('/404', '404', '/404/');
        }

        foreach($rawList as $url)
        {
            $prepedList[] = 'text:'. $url;
            $ques[] = '?';
        }
        $questions = implode(',', $ques);
        $prepedList = array_reverse($prepedList);
        $prepedList = array_merge($prepedList, $prepedList);

        $meta = fw::prep('SELECT * FROM meta WHERE url IN ('.$questions.')
                           ORDER BY FIELD(url, '.$questions.')', $prepedList)->select();
        if($meta)
        {
            foreach($meta->toArray() as $dataList)
            {
                foreach($dataList as $column => $value)
                {
                    $metaData[$column][] = $value;
                }
            }

            self::$metaArray = $metaData;
        }

    }

    public static function get($name)
    {
        if(!self::$metaArray) return false;
        if(!isset(self::$metaArray[$name])) return false;

        $return = '';

        foreach(self::$metaArray[$name] as $key => $data)
        {
            if($data != '')
            {
                if(isset(self::$metaArray[$name . "_append"][$key]) && self::$metaArray[$name . "_append"][$key] == 1) {
                    $return .= $data;
                } else {
                    $return = $data;
                }
            }
        }

        if(is_array(self::$variables))
        {
            foreach(self::$variables as $variable => $value)
            {
                $return = str_replace('{{'.$variable.'}}', $value, $return);
            }
        }

        return $return;
    }

    public static function variables ($array)
    {
        if(!is_array($array)) trigger_error('Input must be an array!');
        self::$variables = $array;
    }

    public static function urlCondition($url)
    {
        //Make url lowercase
        $url = strtolower($url);

        //Make sure it starts with a /
        $url = (substr($url, 0, 1) != '/') ? '/' . $url  : $url ;

        //Make sure it ends with a /
        $url = (substr($url, -1) != '/') ? $url . '/'  : $url ;

        return $url;
    }
}