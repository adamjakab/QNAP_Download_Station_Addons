<?php
/**
 * Class Addon
 */
class DownloadStationAddon
{
    /** @var int  */
    const HARD_LIMIT = 10;

    /** @var string  */
    private $url;

    /** @var bool  */
    private $enableLogging = false;

    /**
     * Addon constructor.
     * @param null $url
     * @param null $username
     * @param null $password
     * @param null $meta
     */
    public function __construct($url = null, $username = null, $password = null, $meta = NULL) {
        $this->url = $url;

        // These will not be present when called from the web interface
        if (isset($_ENV["SHELL"]) || isset($_ENV["TERM"]) || isset($_ENV["USER"])) {
            $this->enableLogging = true;
        }
    }

    protected function log($msg) {
        if ($this->enableLogging) {
            print($msg . "\n");
        }
    }

}
