<?php

class PartyHelper
{
    protected static $_term_cache = [];

    public static function getImageByTermAndName($term, $name)
    {
        if (!array_key_exists($term, self::$_term_cache)) {
            $params = [];
            $params[] = "屆={$term}";
            $params[] = 'output_fields=黨籍';
            $params[] = 'output_fields=委員姓名';
            $ret = LYAPI::apiQuery("/legislators?".implode('&', $params), "查詢第 {$term} 屆立委資料");
            self::$_term_cache[$term] = [];
            foreach ($ret->legislators as $legislator) {
                self::$_term_cache[$term][$legislator->委員姓名] = $legislator->黨籍;
            }
        }
        if (array_key_exists($name, self::$_term_cache[$term])) {
            return self::getImage(self::$_term_cache[$term][$name]);
        }
        return self::getImage($name);
    }

    public static function getImage($input)
    {
        $img_paths = self::$img_paths;
        foreach ($img_paths as $key_name => $path) {
            if (mb_strpos($input, $key_name) !== false) {
                return $path;
            }
        }
        return NULL;
    }

    public static $img_paths = [
        '民主進步黨' => '/static/images/party/dpp.svg',
        '民進黨' => '/static/images/party/dpp.svg',
        '中國國民黨' => '/static/images/party/kmt.svg',
        '國民黨' => '/static/images/party/kmt.svg',
        '勞動黨' => '/static/images/party/lp.svg',
        '無黨籍' => '/static/images/party/no_party.svg',
        '無黨團結聯盟' => '/static/images/party/non.svg',
        '新黨' => '/static/images/party/np.svg',
        '親民黨' => '/static/images/party/pfp.svg',
        '社會民主黨' => '/static/images/party/sd.svg',
        '台灣民眾黨' => '/static/images/party/tpp.svg',
        '民眾黨' => '/static/images/party/tpp.svg',
        '台灣基進' => '/static/images/party/tsp.svg',
        '台灣團結聯盟' => '/static/images/party/tsu.svg',
        '正神名黨' => '/static/images/party/zsm.svg',
        '時代力量' => '/static/images/party/tnpp.svg',
        'none' => '/static/images/party/none.svg',
    ];
}
