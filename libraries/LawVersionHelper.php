<?php

class LawVersionHelper
{
    public static function getVersions($law_id, $version_id_input)
    {
        $res = LYAPI::apiQuery("/law/{$law_id}/versions", "查詢 {$law->名稱} 各法律版本");
        $res_total = $res->total ?? 0;
        if ($res_total == 0) {
            return NULL;
        }

        $versions = $res->lawversions ?? [];
        if ($version_id_input != 'latest') {
            $invalid_version = true;
            foreach ($versions as $version) {
                $version_id = $version->版本編號 ?? NULL;
                if ($version_id_input == $version_id) {
                    $invalid_version = false;
                    $version_id_selected = $version_id;
                    $version_selected = $version;
                    break;
                }
            }
        }

        usort($versions, function($v1, $v2) {
            $date_v1 = $v1->日期 ?? '';
            $date_v2 = $v2->日期 ?? '';
            return $date_v2 <=> $date_v1;
        });

        if ($version_id_input == 'latest') {
            foreach ($versions as $version) {
                $version_id = $version->版本編號 ?? NULL;
                if (isset($version_id)) {
                    $version_id_selected = $version_id;
                    $version_selected = $version;
                    break;
                }
            }
        }

        $versions = array_map(function ($version){
            $version->民國日期 = self::getMinguoDate($version->日期);
            return $version;
        }, $versions);
        $version_selected->民國日期 = self::getMinguoDate($version_selected->日期);

        if ($invalid_version) {
            return (object) [
                'versions' => $versions,
            ];
        }

        return (object) [
            'versions' => $versions,
            'version_selected' => $version_selected,
            'version_id_selected' => $version_id_selected,
        ];
    }

    public static function getMinguoDate($version_date)
    {
        [$year, $month, $day] = explode('-', $version_date);
        $minguo = $year - 1911;
        return "民國 {$minguo} 年 {$month} 月 {$day} 日";
    }
}
