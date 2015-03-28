<?php

$disclude = array(
    '404', '/404', '/404/', '404/'
);

class SiteMap
{
    public static $metaArray = null;
    public static $allMetaData = null;

    public static function getAllMetaData ()
    {
        $metaData = fw::select('SELECT * FROM meta');
        if($metaData)
        {
            foreach($metaData as $meta)
            {
                $allMetaData[$meta->url] = array(
                    'active'                     => $meta->active,
                    'date_content_last_modified' => $meta->date_content_last_modified,
                    'changefreq'                 => $meta->changefreq,
                    'priority'                   => $meta->priority
                );
            }
            self::$allMetaData = $allMetaData;
        }
    }
    public static function setupMetaVariables($uri)
    {
        self::getAllMetaData();

        $urlList = Route::generateUrlPossibilities($uri);
        $urlList = array_reverse($urlList);

        unset($searchList);
        foreach($urlList as $url)
        {
            if(array_key_exists($url, self::$allMetaData))
            {
                $searchList[] = self::$allMetaData[$url];
            }
        }

        foreach($searchList as $dataList)
        {
            foreach($dataList as $column => $value)
            {
                $metaData[$column][] = $value;
            }
        }

        self::$metaArray = $metaData;


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
                $return = $data;
            }
        }

        return $return;
    }
}

header('Content-type: text/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';

?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
$pages = fw::select('SELECT * FROM meta WHERE url NOT LIKE ("%*%") AND url NOT IN ("'.implode('","',$disclude).'") AND `active` = 1 AND `active_date` < CURRENT_TIMESTAMP');
if($pages) {
    foreach($pages as $page)
    {
        $pageUrl = Meta::urlCondition($page->url);
        SiteMap::setupMetaVariables($pageUrl);

        $url = App::get('url') . $pageUrl;
        $date = (SiteMap::get('date_content_last_modified') != '') ? date('Y-m-d', strtotime(SiteMap::get('date_content_last_modified'))) : date('Y-m-d');
        $changefreq = (SiteMap::get('changefreq') != '') ? SiteMap::get('changefreq') : 'monthly';
        $priority = (SiteMap::get('priority') != '') ? SiteMap::get('priority') : '0.8';
?>
    <url>
        <loc><?= $url;?></loc>
        <lastmod><?= $date; ?></lastmod>
        <changefreq><?= $changefreq; ?></changefreq>
        <priority><?= $priority; ?></priority>
    </url>
<?php
    }
}
?>
</urlset> 

