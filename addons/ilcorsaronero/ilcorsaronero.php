<?php
require_once "../helpers/AddonHelper.php";
require_once "../helpers/DownloadStationAddon.php";
if (!class_exists("SearchLink")) {
    require_once "../helpers/SearchLink.php";
}

/**
 * Class IlCorsaroNero
 */
class IlCorsaroNero extends DownloadStationAddon implements ISite, ISearch
{
    /** @var int  */
    const HARD_LIMIT = 10;

    /** @var string */
    const SITE = "https://ilcorsaronero.link";

    /**
     * @param $keyword
     * @param $limit
     * @param $category
     * @return array
     */
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


        // For each search link make sure we have a download link (enclosure_url)
        $this->log("Elaborating Links: " . count($found));
        if ($len = count($found) > 0) {
            for ($i = 0; $i < count($found); ++$i) {
                /** @var SearchLink $searchItem */
                $searchItem = $found[$i];
                if(!isset($searchItem->enclosure_url) || empty($searchItem->enclosure_url)) {
                    $request = [
                        "url" => $searchItem->link,
                        "body" => true
                    ];
                    $response = $ajax->request($request, function($_, $_, $_, $body, $_) use(&$searchItem) {
                        $enclosure_url = $this->ElaborateDetailPage($body);
                        $searchItem->enclosure_url = $enclosure_url;
                    });
                }
            }
        }

        $this->log("Done. Results:");
        return $found;
    }

    public function ElaborateDetailPage($body)
    {
        $answer = false;

        $this->log("Elaborating detail page...");

        //$this->log("\n" . $body);

        //magnet:?xt=urn:btih:HASH&dn=NAME
        $pattern = '#' .
            '<a class="forbtn magnet" href="(?P<magnet>magnet:\?xt=urn:btih:[^"]*)"' .
            '#siU'
        ;
        preg_match_all($pattern, $body, $matches);

        //$this->log("\nMagnet matches" . var_dump($matches));

        if(isset($matches["magnet"][0]) && !empty($matches["magnet"][0])) {
            $answer = $matches["magnet"][0];
        }

        return $answer;
    }

    public function ElaborateSearchPage($body, &$page, &$found, &$limit) {
        $this->log("Elaborating search page...");

        $pattern = '#' .
            '<tr class="(even|odd)".*<a.*>(?<category>.*)</td>.*<a class="tab" HREF="(?<link>.*)" >(?<name>.*)</A>.*' .
            '<td.*>(?P<size>[0-9.]*) (?P<unit>[a-zA-Z]*)</font>' .
            '.*>(?<time>[0-9]{2}\.[0-9]{2}\.[0-9]{2}).*>(?<seeds>[0-9]{1,})<.*' .
            '>(?<leechers>[0-9]{1,})</font></td></TR>' .
            '#siU'
        ;
        preg_match_all($pattern, $body, $matches);

        //$this->log("Matches: " . json_encode($matches));
        if (!$matches || ($len = count($matches["name"])) == 0 ) {
            $page = false;
            return;
        }

        for ($i = 0 ; $i < $len ; ++$i) {
            try {
                $sl = AddonHelper::getSearchLink(
                    "ilcorsaronero",
                    $matches["name"][$i],
                    $matches["link"][$i],
                    $matches["size"][$i],
                    $matches["unit"][$i],
                    $matches["seeds"][$i],
                    $matches["leechers"][$i],
                    $matches["time"][$i],
                    $matches["category"][$i],
                    null
                );
            } catch (\Exception $e) {
                $this->log("Bad Search Link! " .$e->getMessage());
                continue;
            }

            //$this->log("\n" . var_dump($sl));
            $found[]= $sl;

            if (count($found) >= $limit) {
                $page = false;
                break;
            }
        }

        $this->log(">Page: " . $page);
        $this->log(">Found: " . count($found));
        $this->log(">Limit: " . $limit);

        $page++;
    }

}
