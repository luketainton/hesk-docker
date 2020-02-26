<?php
/**
 *
 * This file is part of HESK - PHP Help Desk Software.
 *
 * (c) Copyright Klemen Stirn. All rights reserved.
 * https://www.hesk.com
 *
 * For the full copyright and license agreement information visit
 * https://www.hesk.com/eula.php
 *
 */

require(HESK_PATH . 'inc/htmlpurifier/HTMLPurifier.standalone.php');

class HeskHTMLPurifier extends HTMLPurifier
{
    private $allowIframes;
    private $cacheDir;

    public function __construct($cacheDir = 'cache', $allowIframes = 1)
    {
        $this->allowIframes = $allowIframes;
        $this->cacheDir = $this->setupCacheDir($cacheDir);
    }

    public function heskPurify($content)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Attr.AllowedRel', array('follow', 'referrer', 'nofollow', 'noreferrer') );
        $config->set('Attr.AllowedFrameTargets', array('_blank', '_self', '_parent', '_top') );
        $config->set('Cache.SerializerPath', $this->cacheDir);
        $config->set('URI.AllowedSchemes', array(
                'http' => true,
                'https' => true,
                'mailto' => true,
                'ftp' => true,
                'nntp' => true,
                'news' => true,
                'tel' => true,
                'data' => true,
            )
        );

        if ($this->allowIframes)
        {
            require(HESK_PATH . 'inc/htmlpurifier/custom/heskIframe.php');
            $config->set('Filter.Custom', array(new HTMLPurifier_Filter_HeskIframe()));
        }

        $purifier = new HTMLPurifier($config);
        return $purifier->purify($content);
    }

    private function setupCacheDir($cache_dir)
    {
        $cache_dir = dirname(dirname(dirname(__FILE__))).'/'.$cache_dir.'/hp';

        if (is_dir($cache_dir) || ( @mkdir($cache_dir, 0777) && is_writable($cache_dir) ) )
        {
            return $cache_dir;
        }

        return null;
    }
}
