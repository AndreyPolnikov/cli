<?php

require_once __DIR__ . '/../wp-load.php';

abstract class WpCliLoader
{
    public function __construct()
    {
        if (php_sapi_name() !== 'cli') {
            wp_die('Not available');
        }
    }

    abstract public static function handle();

    protected function adminInfo($msg, $newLine = true)
    {
        fwrite(STDOUT, $msg . ($newLine ? PHP_EOL : ''));
    }

    protected function getLanguages()
    {
        $wpSitePress = $this->getWpSitePress();

        if (!$wpSitePress) {
            return [];
        }

        return array_keys($wpSitePress->get_active_languages(false, true, 'id') ?? []);
    }

    protected function cliChangeLang($lang)
    {
        $sitePress = $this->getWpSitePress();
        if ($sitePress) {
            $sitePress->switch_lang($lang);
        }
    }

    protected function getLanguagesPost($lang, $ids = false)
    {
        $this->cliChangeLang($lang);

        $queryData = [
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'suppress_filters' => false
        ];

        if ($ids) {
            $queryData['fields'] = 'ids';
        }

        $res = query_posts($queryData);

        $frontId = (int) (get_option('page_on_front') ?? 0);
        if ($ids) {
            $res = array_merge($res, [$frontId]);
        } else {
            $res[] = get_post($frontId);
        }

        return $res;
    }

    /**
     * @return SitePress || null
     */
    private function getWpSitePress(): SitePress
    {
        global $sitepress;
        return $sitepress;
    }
}