<?php

class LawVersionHelper
{
    public static function getVersionsData($law_id, $version_id_input)
    {
        $res = LYAPI::apiQuery("/law/{$law_id}/versions", "查詢 {$law->名稱} 各法律版本");
        $res_total = $res->total ?? 0;
        if ($res_total == 0) {
            return NULL;
        }

        $versions = $res->lawversions ?? [];
        $version_cnt = count($versions);
        usort($versions, function($v1, $v2) {
            $date_v1 = $v1->日期 ?? '';
            $date_v2 = $v2->日期 ?? '';
            return $date_v2 <=> $date_v1;
        });

        if ($version_id_input != 'latest') {
            $invalid_version = true;
            foreach ($versions as $idx => $version) {
                $version_id = $version->版本編號 ?? NULL;
                if ($version_id_input == $version_id) {
                    $invalid_version = false;
                    $version_id_selected = $version_id;
                    $version_selected = $version;
                    if ($idx < $version_cnt - 1) {
                        $version_id_previous = $versions[$idx + 1]->版本編號 ?? NULL;
                        $version_previous = $versions[$idx + 1];
                    }
                    break;
                }
            }
        }

        if ($version_id_input == 'latest') {
            foreach ($versions as $idx => $version) {
                $version_id = $version->版本編號 ?? NULL;
                if (!is_null($version_id)) {
                    $version_id_selected = $version_id;
                    $version_selected = $version;
                    if ($idx < $version_cnt - 1) {
                        $version_id_previous = $versions[$idx + 1]->版本編號 ?? NULL;
                        $version_previous = $versions[$idx + 1];
                    }
                    break;
                }
            }
        }

        $versions = array_map(function ($version){
            $version->民國日期 = self::getMinguoDate($version->日期);
            $version->民國日期_format2 = self::getMinguoDateFormat2($version->日期);
            return $version;
        }, $versions);

        $term_dates = LyDateHelper::$term_dates;
        $versions_in_terms = array_fill_keys(array_keys($term_dates), []);
        foreach ($versions as $version) {
            $version_date = $version->日期;
            foreach ($term_dates as $term => $interval) {
                if ($interval[0] <= $version_date and $version_date <= $interval[1]) {
                    $versions_in_terms[$term][] = $version;
                    if ($version_id_selected == $version->版本編號) {
                        $term_selected = $term;
                    }
                    break;
                }
            }
        }

        //filter out term with no version to choose
        $versions_in_terms_filtered = array_filter($versions_in_terms, function($versions) {
            return !empty($versions);
        });

        if ($invalid_version) {
            return (object) [
                'versions' => $versions,
                'versions_in_terms' => $versions_in_terms,
                'versions_in_terms_filtered' => $versions_in_terms_filtered,
            ];
        }

        $version_selected->民國日期 = self::getMinguoDate($version_selected->日期);
        $version_selected->民國日期_format2 = self::getMinguoDateFormat2($version_selected->日期);

        $versions_data = (object) [
            'versions' => $versions,
            'versions_in_terms' => $versions_in_terms,
            'versions_in_terms_filtered' => $versions_in_terms_filtered,
            'version_selected' => $version_selected,
            'version_id_selected' => $version_id_selected,
            'term_selected' => $term_selected,
        ];

        if (!is_null($version_id_previous)) {
            $versions_data->version_id_previous = $version_id_previous;
            $version_previous->民國日期 = self::getMinguoDate($version_previous->日期);
            $version_previous->民國日期_format2 = self::getMinguoDateFormat2($version_previous->日期);
            $versions_data->version_previous = $version_previous;
        }

        return $versions_data;
    }

    public static function getVersionsForSingle($law_id, $version_id_input, $law_content_name)
    {
        $versions_data = self::getVersionsData($law_id, $version_id_input);
        $versions = $versions_data->versions;
        $version_id_selected = $versions_data->version_id_selected;
        if (is_null($versions)) {
            return NULL;
        }
        $law_content_name_encoded = mb_ereg_replace(' ', '%20', $law_content_name);
        $versions = array_map(function ($version) use ($law_content_name, $law_content_name_encoded){
            $version_id = $version->版本編號;
            $res = LYAPI::apiQuery("/law_contents?版本編號={$version_id}&條號={$law_content_name_encoded}",
                "查詢 law_content: 版本編號:{$version_id}, 條號:{$law_content_name}"
            );
            $res_total = $res->total ?? 0;
            if ($res_total > 0) {
                $law_contents = $res->lawcontents ?? [];
                $version->law_content_id = $law_contents[0]->法條編號;
            }
            return $version;
        }, $versions);
        $versions = array_filter($versions, function ($version) {
            return property_exists($version, 'law_content_id');
        });

        if (isset($version_id_selected)) {
            $version_selected = array_filter($versions, function ($version) use ($version_id_selected) {
                $version_id = $version->版本編號;
                return ($version_id == $version_id_selected);
            });
            if (empty($version_selected)) {
                unset($versions_data->version_id_selected);
                unset($versions_data->version_selected);
            } else {
                $versions_data->version_selected = $version_selected[0];
            }
        }

        $versions_data->versions = $versions;
        return $versions_data;
    }

    public static function getVersionsWithProgresses($law_id, $version_id_input)
    {
        $versions_data = self::getVersionsData($law_id, $version_id_input);
        $versions_in_terms = $versions_data->versions_in_terms;
        $version_selected = $versions_data->version_selected;
        $version_id_selected = $versions_data->version_id_selected;
        $term_selected = $versions_data->term_selected;

        $term_dates = LyDateHelper::$term_dates;

        foreach ($versions_in_terms as $term => $versions) {
            $version_id = "{$law_id}:{$term}-progress";
            $version = (object) [
                '版本編號' => $version_id,
            ];
            if (is_null($version_id_selected) and $version_id_input == $version_id) {
                $res = LYAPI::apiQuery("/law/{$law_id}/progress?屆={$term}", "查詢 law_id: {$law_id} 第 {$term} 屆 progress");
                $bill_log = $res->歷程[0]->bill_log;
                $version->歷程 = $bill_log;
                $version_selected = $version;
                $version_id_selected = $version_id;
                $term_selected = $term;
            }
            $versions_in_terms[$term][] = $version;
        }

        //filter out term with no version to choose
        $versions_in_terms = array_filter($versions_in_terms, function($versions) {
            return !empty($versions);
        });

        //repack versions_data
        $versions_data->versions_in_terms = $versions_in_terms;
        $versions_data->version_selected = $version_selected;
        $versions_data->version_id_selected = $version_id_selected;
        $versions_data->term_selected = $term_selected;

        return $versions_data;
    }

    public static function getMinguoDate($version_date)
    {
        [$year, $month, $day] = explode('-', $version_date);
        $minguo = intval($year) - 1911;
        return "民國 {$minguo} 年 {$month} 月 {$day} 日";
    }

    public static function getMinguoDateFormat2($version_date)
    {
        [$year, $month, $day] = explode('-', $version_date);
        $minguo = intval($year) - 1911;
        return "{$minguo}/{$month}/{$day}";
    }
}
