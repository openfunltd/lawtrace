<?php

class IndexHelper
{
    public static function getThirdReadList()
    {
        $limit = 100;
        $ret = json_decode(file_get_contents('https://v2.ly.govapi.tw/laws?limit=' . $limit));
        $laws = [];
        $versions = [];
        foreach ($ret->laws as $law) {
            $version_id = "{$law->法律編號}:{$law->最新版本->版本編號}";
            $laws[$version_id] = [
                'law' => $law,
            ];
            $versions[] = "版本編號={$version_id}";
        }

        // 再抓看看有沒有版本歷程
        $lawversions = [];
        foreach (array_chunk($versions, 50) as $chunk_versions) {
            $ret = json_decode(file_get_contents('https://v2.ly.govapi.tw/law_versions?' . implode('&', $chunk_versions)));
            $lawversions = array_merge($lawversions, $ret->lawversions);
        }

        foreach ($lawversions as $law_version) {
            $version_id = $law_version->版本編號;
            $laws[$version_id]['version'] = [
                '提案人' => [],
                '有院版' => false,
                '有完整歷程' => false,
                '最早提案日期' => null,
                '審查報告日期' => null,
            ];

            if ($law_version->歷程 ?? false) {
                $laws[$version_id]['version']['有完整歷程'] = true;
                foreach ($law_version->歷程 as $history) {
                    if ($history->進度 == '一讀' and ($history->主提案 ?? false)) {
                        $laws[$version_id]['version']['提案人'][] = $history->主提案;
                        foreach (['行政院', '司法院', '監察院', '考試院'] as $院) {
                            if (strpos($history->主提案, $院) !== false) {
                                $laws[$version_id]['version']['有院版'] = true;
                            }
                        }
                        if (is_null($laws[$version_id]['version']['最早提案日期'])) {
                            $laws[$version_id]['version']['最早提案日期'] = $history->會議日期;
                        }
                    }
                    if ($history->進度 == '委員會審查') {
                        $laws[$version_id]['version']['審查報告日期'] = $history->會議日期;
                    }
                }
            }
        }
        return $laws;
    }
}
