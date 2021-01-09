<?php
require_once "../helpers/AddonHelper.php";
if (!class_exists("SearchLink")) {
    require_once "../helpers/SearchLink.php";
}

/**
 * Class OneThreeThreeSevenX ::: 1337X
 */
class OneThreeThreeSevenX implements ISite, ISearch
{
    /** @var bool  */
    const ENABLE_LOGGING = false;

    /** @var int  */
    const HARD_LIMIT = 10;

    /** @var string  */
    private $url;

    /** @var string */
    const SITE = "https://1337x.to";

    /**
     * OneThreeThreeSevenX constructor.
     * @param null $url
     * @param null $username
     * @param null $password
     * @param null $meta
     */
    public function __construct($url = null, $username = null, $password = null, $meta = NULL) {
        $this->url = $url;
    }

    private function log($msg) {
        if (self::ENABLE_LOGGING) {
            print($msg . "\n");
        }
    }

    public function Search($keyword, $limit, $category) {
        if (self::HARD_LIMIT && self::HARD_LIMIT < $limit) {
            $limit = self::HARD_LIMIT;
        }
        $this->log("Searching '$keyword' with limit($limit) in category($category)...");
        $page = 1;
        $ajax = new Ajax();
        $found = [];

        // Request the search page, elaborate the HTML and put search results in $found array
        $request = [
            "url" => OneThreeThreeSevenX::SITE."/category-search/$keyword/Movies/$page/",
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

        //print("\n" . $body);

        //magnet:?xt=urn:btih:HASH&dn=NAME
        $pattern = '#' .
            'href="(?P<magnet>magnet:\?xt=urn:btih:[^"]*)"' .
            '#siU'
        ;
        preg_match_all($pattern, $body, $matches);

        //$this->log("\nMagnet matches" . var_dump($matches));

        if(isset($matches["magnet"][0])) {
            $answer = $matches["magnet"][0];
        }

        return $answer;
    }

    public function ElaborateSearchPage($body, &$page, &$found, &$limit) {
        $this->log("Elaborating search page...");

        $pattern = '#' .
            '<tr>.*' .
            '<td[^>]*><a .*><i .*></i></a><a href="(?P<link>.*)">(?P<name>.*)</a>.*</td>.*' .
            '<td class="coll-2 seeds">(?P<seeds>.*)</td>.*' .
            '<td class="coll-3 leeches">(?P<leechers>.*)</td>.*' .
            '<td class="coll-date">(?P<time>.*)</td>.*' .
            '<td class="coll-4 size [^"]*">(?P<size>.*) (?P<unit>[a-zA-Z]*)<span class="seeds">[^<]*</span></td>.*' .
            '<td class="coll-5 [^"]*"><a href=".*">(?P<uploader>.*)</a></td>.*' .
            '</tr>' .
            '#siU'
        ;
        preg_match_all($pattern, $body, $matches);
        // $this->log("Matches: " . json_encode($matches));

        if (!$matches || ($len = count($matches["name"])) == 0 ) {
            $page = false;
            return;
        }

        for ($i = 0 ; $i < $len ; ++$i) {
            try {
                $sl = $this->getSearchLink(
                    "1337x",
                    $matches["name"][$i],
                    $matches["link"][$i],
                    $matches["size"][$i],
                    $matches["unit"][$i],
                    $matches["seeds"][$i],
                    $matches["leechers"][$i],
                    $matches["time"][$i],
                    "Movie",
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

    /**
     * @param string $source
     * @param string $name
     * @param string $link
     * @param string $size
     * @param string $unit
     * @param string $seeds
     * @param string $leechers
     * @param string $time
     * @param string $category
     * @param string $enclosure_url
     * @return SearchLink
     * @throws Exception
     */
    protected function getSearchLink($source, $name, $link, $size, $unit, $seeds, $leechers, $time, $category, $enclosure_url) {
        $sl = new SearchLink();

        // Source
        if (empty($source)) {
            throw new \Exception("SearchLink: Undefined source!");
        }
        $sl->src = $source;

        // Name
        $name = trim(strip_tags($name));
        if (empty($name)) {
            throw new \Exception("SearchLink: Undefined name!");
        }
        $sl->name = $name;

        // Link: points to the detail page on which the resource is found
        $link = trim($link);
        if (empty($link)) {
            throw new \Exception("SearchLink: Undefined link!");
        }
        $sl->link = OneThreeThreeSevenX::SITE . $link;

        // Size
        $size = !empty($size) ? floatval($size) : 0;
        $unit = !empty($unit) ? trim($unit) : "";
        $sl->size  = $size * AddonHelper::UnitSize($unit);

        // Seeds
        $sl->seeds = intval($seeds);

        // Peers
        $sl->peers = intval($leechers);

        // Time
        if (!empty($time)) {
            try {
                $sl->time = new DateTime($time);
            } catch (Exception $e) {
                $sl->time = new DateTime();
            }
        }

        // Category
        $category = !empty($category) ? trim($category) : "Unknown";
        $sl->category = $category;

        // Enclosure URL: The link to the resource file to be downloaded
        if (!empty($enclosure_url)) {
            $sl->enclosure_url = $enclosure_url;
        }

        // Test
        $sl->test = "Jack";

        return $sl;
    }
}

