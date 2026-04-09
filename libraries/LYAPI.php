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

    public static function apiQuery($url, $reason, $cache = 3600)
    {
        $url = 'https://' . getenv('LYAPI_HOST') . $url;

        $cache_key = null;
        $cache_file = null;

        if (!is_null($cache)) {
            $cache_key = 'lyapi_' . crc32($url) . '_' . md5($url);
            $cache_file = "/tmp/lyapicache-{$cache_key}.json";
            if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache and !($_GET['nocache'] ?? false)) {
                $res_json = json_decode(file_get_contents($cache_file));
                if (is_null(self::$log)) {
                    self::$log = [];
                }
                self::$log[] = [$url, $reason, 'cached'];
                return $res_json;
            }
        }

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
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        $start = microtime(true);
        $res = curl_exec($curl);
        $delta = microtime(true) - $start;
        if ($delta > 1) {
            file_put_contents("/tmp/lyapi-slow-" . date('Ymd'), json_encode([
                'time' => date('Y-m-d H:i:s'),
                'query' => $_SERVER['REQUEST_URI'],
                'url' => $url,
                'reason' => $reason,
                'delta' => $delta,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
        }
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
