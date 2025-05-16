<?php

class LYAPI
{
    protected static $log = [];
    public static function hasLog()
    {
        return count(self::$log) > 0;
    }

    public static function getLogs()
    {
        return self::$log;
    }

    public static function apiQuery($url, $reason)
    {
        $url = 'https://' . getenv('LYAPI_HOST') . $url;

        $curl = curl_init();
        if (getenv('LYAPI_TOKEN')) {
            if (strpos($url, '?') === false) {
                $api_url = $url . '?token=' . getenv('LYAPI_TOKEN');
            } else {
                $api_url = $url . '&token=' . getenv('LYAPI_TOKEN');
            }
        }
        curl_setopt($curl, CURLOPT_URL, $api_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // referer
        curl_setopt($curl, CURLOPT_REFERER, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        // user agent
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $res = curl_exec($curl);
        $res_json = json_decode($res);
        curl_close($curl);
        if (is_null(self::$log)) {
            self::$log = [];
        }
        self::$log[] = [$url, $reason];

        return $res_json;
    }

    public static function addTemplateLog()
    {
        self::$log[] = ['template', 'template'];
    }
}
