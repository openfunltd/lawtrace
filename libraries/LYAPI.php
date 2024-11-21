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
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
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
