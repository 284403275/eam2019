<?php


namespace App\SAP\Documentation;


use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\DomCrawler\Crawler;

class Documentation
{
    static $defaultVersion = '0.1.0';

    /**
     * The filesystem implementation.
     *
     * @var Filesystem
     */
    protected $files;
    /**
     * The cache implementation.
     *
     * @var Cache
     */
    protected $cache;

    public function __construct(Filesystem $files, Cache $cache)
    {
        $this->files = $files;
        $this->cache = $cache;
    }

    public static function defaultVersion()
    {
        return self::$defaultVersion;
    }

    public static function getDocVersions()
    {
        return [
            '0.1.0' => '0.1.0'
        ];
    }

    public function get($version, $page)
    {
//        return $this->cache->remember('docs.' . $version . '.' . $page, 5, function () use ($version, $page) {
        $path = public_path('specs/' . $version . '/' . $page . '.md');

        if ($this->files->exists($path)) {
            return $this->replaceLinks($version, markdown($this->files->get($path)));
        }

        return null;
//        });
    }

    /**
     * Get the documentation index page.
     *
     * @param  string $version
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getIndex($version)
    {

//        return $this->cache->remember('docs.'.$version.'.index', 5, function () use ($version) {
        $path = public_path('specs/' . $version . '/documentation.md');

        if ($this->files->exists($path)) {

            $index = $this->replaceLinks($version, markdown($this->files->get($path)));


            $crawler = (new Crawler($index))->filter('ul li');

            foreach ($crawler as $crawl) {
                $links = (new Crawler($crawl))->filter('ul li a');
                $header = (new Crawler($crawl))->filter('h2');
                foreach ($links as $link) {
                    $nav[] = [
                        'header' => $header->text(),
                        'link' => $link->getAttribute('href'),
                        'text' => $link->textContent
                    ];
                }
            }

            return $nav;
//                }
        }
        return null;
//        });
    }

    /**
     * Check if the given section exists.
     *
     * @param  string $version
     * @param  string $page
     * @return boolean
     */
    public function sectionExists($version, $page)
    {
        return $this->files->exists(
            public_path('specs/' . $version . '/' . $page . '.md')
        );
    }

    public static function replaceLinks($version, $content)
    {
        $str = str_replace('{{version}}', $version, $content);

        return str_replace('{{image}}', '/specs/' . $version . '/images', $str);
    }
}