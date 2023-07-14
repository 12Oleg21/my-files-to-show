<?php

namespace app\modules\sitemap\components;

use Yii;
use Exception;
use XMLWriter;

use yii\helpers\Url;

/**
 * Sitemap component
 */
class Sitemap {

    /**
     * @var XMLWriter
     */
    private $writer;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $filename = 'sitemap';

    /**
     * @var int
     */
    private $current_item = 0;

    /**
     * @var int
     */
    private $current_sitemap = 0;

    const EXT = '.xml';
    const SCHEMA = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    const DEFAULT_PRIORITY = 0.5;
    const ITEM_PER_SITEMAP = 50000;
    const SEPERATOR = '-';

    /**
     * @param string $domain
     * @param string $path
     *
     * @throws Exception
     */
    public function __construct($domain = null, $path = null) {
        $domain = $domain ?: Url::base(true);
        $path = $path ?: Yii::getAlias('@runtime/sitemap/');

        if ( ! is_dir($path) && ! mkdir($path) && ! is_writable($path)) {
            throw new Exception("Error. You must create '{$path}'' writable directory.");
        }

        $this->setDomain($domain);
        $this->setPath($path);
    }

    /**
     * @return string
     */
    public function getIndexSitemapPath() {
        return $this->getPath() . $this->getFilename() . self::EXT;
    }

    /**
     * @return string
     */
    public function getItemSitemapPath($index) {
        return $this->getPath() . $this->getFilename() . self::SEPERATOR . $index . self::EXT;
    }

    /**
     * Sets root path of the website, starting with http:// or https://
     *
     * @param string $domain
     * @return $this
     */
    public function setDomain($domain) {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Returns root path of the website
     *
     * @return string
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * Assigns XMLWriter object instance
     *
     * @param XMLWriter $writer
     */
    public function setWriter(XMLWriter $writer) {
        $this->writer = $writer;
    }

    /**
     * Returns XMLWriter object instance
     *
     * @return XMLWriter
     */
    public function getWriter() {
        return $this->writer;
    }

    /**
     * Sets paths of sitemaps
     *
     * @param string $path
     * @return $this
     */
    public function setPath($path) {
        $this->path = $path;

        return $this;
    }

    /**
     * Returns path of sitemaps
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Sets filename of sitemap file
     *
     * @param string $filename
     * @return $this
     */
    public function setFilename($filename) {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Returns filename of sitemap file
     *
     * @return string
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * Returns current item count
     *
     * @return int
     */
    public function getCurrentItem() {
        return $this->current_item;
    }

    /**
     * Returns current sitemap file count
     *
     * @return int
     */
    public function getCurrentSitemap() {
        return $this->current_sitemap;
    }

    /**
     * Increases item counter
     *
     */
    private function incCurrentItem() {
        $this->current_item = $this->current_item + 1;
    }

    /**
     * Increases sitemap file count
     *
     */
    private function incCurrentSitemap() {
        $this->current_sitemap = $this->current_sitemap + 1;
    }

    /**
     * Prepares sitemap XML document
     *
     */
    private function startSitemap() {
        $this->setWriter(new XMLWriter());

        /**
         * For example:
         *  http://example.com/sitemap-0.xml
         *  http://example.com/sitemap-1.xml
         *  http://example.com/sitemap-2.xml
         */
        $this->getWriter()->openURI($this->getItemSitemapPath($this->getCurrentSitemap()));
        $this->getWriter()->startDocument('1.0', 'UTF-8');
        $this->getWriter()->setIndent(true);
        $this->getWriter()->startElement('urlset');
        $this->getWriter()->writeAttribute('xmlns', self::SCHEMA);
    }

    /**
     * Finalizes tags of sitemap XML document.
     */
    private function endSitemap() {
        if ( ! $this->getWriter()) {
            $this->startSitemap();
        }

        $this->getWriter()->endElement();
        $this->getWriter()->endDocument();
    }

    /**
     * Adds an item to sitemap
     *
     * @param string        $loc        URL of the page. This value must be less than 2,048 characters.
     * @param float|string  $priority   The priority of this URL relative to other URLs on your site. Valid values range from 0.0 to 1.0.
     * @param string        $changefreq How frequently the page is likely to change. Valid values are always, hourly, daily, weekly, monthly, yearly and never.
     * @param string|int    $lastmod    The date of last modification of url. Unix timestamp or any English textual datetime description.
     *
     * @return $this
     */
    public function addItem($loc, $priority = self::DEFAULT_PRIORITY, $changefreq = NULL, $lastmod = NULL) {
        if (($this->getCurrentItem() % self::ITEM_PER_SITEMAP) == 0) {
            if ($this->getWriter() instanceof XMLWriter) {
                $this->endSitemap();
            }

            $this->startSitemap();
            $this->incCurrentSitemap();
        }

        $this->incCurrentItem();
        $this->getWriter()->startElement('url');
        $this->getWriter()->writeElement('loc', $this->getDomain() . $loc);
        $this->getWriter()->writeElement('priority', $priority);

        if ($changefreq) {
            $this->getWriter()->writeElement('changefreq', $changefreq);
        }
        if ($lastmod) {
            $this->getWriter()->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
        }

        $this->getWriter()->endElement();

        return $this;
    }

    /**
     * Writes Google sitemap index for generated sitemap files
     *
     * @param string $loc Accessible URL path of sitemaps
     * @param string|int $lastmod The date of last modification of sitemap. Unix timestamp or any English textual datetime description.
     */
    public function createSitemapIndex($lastmod = 'Today') {
        $this->endSitemap();
        $indexwriter = new XMLWriter();

        $indexwriter->openURI($this->getIndexSitemapPath());
        $indexwriter->startDocument('1.0', 'UTF-8');
        $indexwriter->setIndent(true);
        $indexwriter->startElement('sitemapindex');
        $indexwriter->writeAttribute('xmlns', self::SCHEMA);

        for ($index = 0; $index < $this->getCurrentSitemap(); $index++) {
            $indexwriter->startElement('sitemap');
            $indexwriter->writeElement('loc', $this->getDomain() . '/' . $this->getFilename() . self::SEPERATOR . $index . self::EXT);
            $indexwriter->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
            $indexwriter->endElement();
        }

        $indexwriter->endElement();
        $indexwriter->endDocument();
    }

    /**
     * Prepares given date for sitemap
     *
     * @param string $date Unix timestamp or any English textual datetime description
     * @return string Year-Month-Day formatted date.
     */
    private function getLastModifiedDate($date) {
        if (ctype_digit($date)) {
            return date('Y-m-d', $date);
        } else {
            $date = strtotime($date);

            return date('Y-m-d', $date);
        }
    }
}
