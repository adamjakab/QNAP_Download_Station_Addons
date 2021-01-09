<?php
require_once "../helpers/DownloadStationAddon.php";


/**
 * Class IlCorsaroNero
 */
class IlCorsaroNero extends DownloadStationAddon implements ISite, ISearch
{
    /** @var int  */
    const HARD_LIMIT = 10;

    /** @var string */
    const SITE = "https://ilcorsaronero.link";


    public function Search($keyword, $limit, $category) {
        if (self::HARD_LIMIT && self::HARD_LIMIT < $limit) {
            $limit = self::HARD_LIMIT;
        }
        $this->log("Searching '$keyword' with limit($limit) in category($category)...");

        $page = 1;
        $ajax = new Ajax();
        $found = [];


        // Request the search page, elaborate the HTML and put search results in $found array
        // BASIC: https://ilcorsaronero.link/argh.php?search=notte+da+dimenticare
        // ADVANCED: https://ilcorsaronero.link/adv/notte%20da%20dimenticare.html

        $request = [
            "url" => self::SITE."/argh.php?search=" . urlencode($keyword),
            "body" => true
        ];
        $this->log("Request: " . json_encode($request));

        $response = $ajax->request($request, function($_, $_, $_, $body, $_) use(&$page, &$found, &$limit) {
            $this->ElaborateSearchPage($body, $page, $found, $limit);
        });




        return $found;
    }

    public function ElaborateSearchPage($body, &$page, &$found, &$limit) {
        $this->log("Elaborating search page...");

        $this->log($body);
    }

}