<?php

class PolicyAPI
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

    public static function apiQuery($url, $reason, $cache = null)
    {
        $url = 'https://' . getenv('POLICYAPI_HOST') . $url;

        $cache_key = null;
        $cache_file = null;

        if (!is_null($cache)) {
            $cache_key = 'policyapi_' . crc32($url) . '_' . md5($url);
            $cache_file = "/tmp/policyapicache-{$cache_key}.json";
            if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache) {
                $res_json = json_decode(file_get_contents($cache_file));
                if (is_null(self::$log)) {
                    self::$log = [];
                }
                self::$log[] = [$url, $reason, 'cached'];
                return $res_json;
            }
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $api_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // referer
        curl_setopt($curl, CURLOPT_REFERER, 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        // user agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        $res = curl_exec($curl);
        $res_json = json_decode($res);
        curl_close($curl);
        if (is_null(self::$log)) {
            self::$log = [];
        }
        self::$log[] = [$url, $reason];

        if (!is_null($cache_file)) {
            if (is_null($res_json)) {
                $res_json = new stdClass();
                $res_json->error = 'Invalid JSON response';
            }
            file_put_contents($cache_file, json_encode($res_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        return $res_json;
    }

    public static function addTemplateLog()
    {
        self::$log[] = ['template', 'template'];
    }
}
