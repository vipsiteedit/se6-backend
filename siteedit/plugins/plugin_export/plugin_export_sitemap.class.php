<?php

class plugin_export_sitemap
{
    private $countpos = 0;
    private $maxcount = 30000;
    private $arr = array();
    private $name = 'sitemap';
    private $roots = array();
    private $dir = '';

    private $urllist = array();

    public function __construct()
    {
        $domain = getUrlHost();
        $u = new DB('app_urls', 'au');
        $u->select('au.pattern, au.id_section, ac.seo_enable, ap.updated_at, ap.priority');
        $u->innerJoin('app_pages ap', 'ap.id=au.id_page');
        $u->leftJoin('app_section ac', 'ac.id=au.id_section');
        $urls = $u->getList();
        $this->urllist = array();
        foreach ($urls as $url) {
            if (strpos($url['pattern'], '://') !== false) continue;
            if ($url['idSection']) {
                if ($url['seoEnable']) {
                    foreach ($this->getSectionItems($url['idSection']) as $it) {
                        $this->urllist[] = array(
                            'loc' => str_replace(array('{id}', '{name}'), array($it['id'], $it['name']), $domain . $url['pattern']),
                            'priority' => number_format(($url['priority'] - 1) / 10, 1),
                            'updatedAt' => $it['updatedAt']
                        );
                    }
                }
            } else {
                $this->urllist[] = array(
                    'loc' => $domain . $url['pattern'],
                    'priority' => number_format($url['priority']  / 10, 1),
                    'updatedAt' => $url['updatedAt']
                );
            }
        }
    }

    private function getSectionItems($idSection)
    {
        $u = new DB('app_section_collection');
        $u->select('id, name, url, updated_at');
        $u->where('visible=1');
        $u->andWhere('id_section=?', $idSection);
        return $u->getList();
    }

    private function changefreq($time)
    {
        $changefreq = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');
        $delta = ((time() - $time) / (3600 * 24));
        if ($delta * 24 < 1) $ch = 0;
        elseif ($delta < 1) $ch = 1;
        elseif ($delta <= 3) $ch = 2;
        elseif ($delta <= 7) $ch = 3;
        elseif ($delta <= 180) $ch = 4;
        elseif ($delta <= 360) $ch = 5;
        else $ch = 6;
        return $changefreq[$ch];
    }

    private function setMap($name, $maplist)
    {
        $new = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $new .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n";
        $new .= "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
        $new .= "xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n";
        $new .= "http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";
        foreach ($this->urllist as $item) {
            $date_mod = strtotime($item['updatedAt']);
            $lastmod = date("c", $date_mod);
            $changefreq = $this->changefreq($date_mod);

            $new .= "\t<url>\n";
            $new .= "\t\t<loc>" . $item['loc'] . "</loc>\n";
            $new .= "\t\t<lastmod>" . $lastmod . "</lastmod>\n";
            $new .= "\t\t<changefreq>" . $changefreq . "</changefreq>\n";
            $new .= "\t\t<priority>" . $item['priority'] . "</priority>\n";
            $new .= "\t</url>\n";
        }
        echo $new .= "</urlset>\n";

        //$file = fopen($name . '.xml', "w+");
        //fwrite($file, $new);
        //fclose($file);
    }

    public function execute()
    {
        $up = 0;
        $count = 0;
        $domain = getUrlHost();
        foreach ($this->urllist as $item) {
            $this->arr[] = $item;
            if (count($this->arr) >= $this->maxcount) {
                $this->countpos++;
                $this->setMap(BASE_DIR . '/' . $this->name . $this->countpos, $this->arr);
                unset($this->arr);
                $this->arr = array();
            }
        }


        if ($this->countpos) {
            $new = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $new .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
            for ($i = 1; $i <= $this->countpos; $i++) {
                $new .= "<sitemap>";
                $new .= "\t<loc>" . $domain . "/" . $this->name . $i . ".xml</loc>\n";
                $new .= "\t\t<lastmod>" . date("c", filemtime(BASE_DIR . $this->name . $i . '.xml')) . "</lastmod>\n";
                $new .= "</sitemap>\n";
            }
            $new .= "</sitemapindex>\n";
            $file = fopen(BASE_DIR . '/' . $this->name . '.xml', "w+");
            fwrite($file, $new);
            fclose($file);
        } elseif (count($this->arr)) {
            $this->setMap(BASE_DIR . '/' . $this->name, $this->arr);
        }
    }

}