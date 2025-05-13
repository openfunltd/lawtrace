<?php

class LawVersionHelper
{
    public static function getVersionsData($law_id, $version_id_input)
    {
        $res = LYAPI::apiQuery("/law/{$law_id}/versions", "查詢法律編號：{$law_id} 各法律版本");
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

        $latest_third_reading_date = ($version_cnt > 0) ? $versions[0]->日期 : '1912-01-01';

        $invalid_version = true;
        $version_id_selected = null;

        if ($version_id_input != 'latest') {
            $filtered_versions = array_filter($versions, function ($version) use ($version_id_input) {
                $version_date = $version->日期 ?? NULL;
                $check_date = substr(explode(':', $version_id_input)[1], 0, 10) ?? NULL;
                return 7 * 86400 > abs(strtotime($version_date) - strtotime($check_date));
            });
            foreach ($filtered_versions as $idx => $version) {
                $version_id = $version->版本編號 ?? NULL;
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

        if ($version_id_input == 'latest') {
            foreach ($versions as $idx => $version) {
                $version_id = $version->版本編號 ?? NULL;
                if (!is_null($version_id)) {
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
                'latest_third_reading_date' => $latest_third_reading_date,
                'versions_in_terms' => $versions_in_terms,
                'versions_in_terms_filtered' => $versions_in_terms_filtered,
            ];
        }

        $version_selected->民國日期 = self::getMinguoDate($version_selected->日期);
        $version_selected->民國日期_format2 = self::getMinguoDateFormat2($version_selected->日期);

        $versions_data = (object) [
            'versions' => $versions,
            'latest_third_reading_date' => $latest_third_reading_date,
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
        $version_ids = array_reduce($versions, function ($carry, $version) {
            $carry[] = $version->版本編號;
            return $carry;
        }, []);
        $res = LYAPI::apiQuery('/law_contents?版本編號=' . implode('&版本編號=', $version_ids) . '&limit=9999',
            '查詢所有版本編號的所有條文（之後再依條號 filter）'
        );
        $law_contents = $res->lawcontents ?? [];
        foreach ($versions as $version) {
            $version_id = $version->版本編號;
            foreach ($law_contents as $law_content) {
                if (!property_exists($law_content, '條號')) {
                    continue;
                }
                if ($law_content->版本編號 == $version_id and $law_content->條號 == $law_content_name_encoded) {
                    $version->law_content_id = $law_content->法條編號;
                    break;
                }
            }
        }
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
        $versions_data = self::getVersionsData($law_id, $version_id_input) ?? (object) [];
        $versions_in_terms = $versions_data->versions_in_terms;
        $latest_third_reading_date = $versions_data->latest_third_reading_date;
        $version_selected = $versions_data->version_selected ?? null;
        $version_id_selected = $versions_data->version_id_selected ?? null;
        $term_selected = $versions_data->term_selected ?? null;
        $term_dates = LyDateHelper::$term_dates;
        $version_date = substr(explode(':', $version_id_input)[1], 0, 10) ?? NULL;

        //repack 歷程 as 歷程 in progress
        if ($version_selected->歷程 ?? false) {
            $histories = $version_selected->歷程;
        } else {
            // 沒有歷程，可能是最新的版本，要去 progress API 找
            $res = LYAPI::apiQuery("/law/{$law_id}/progress", "查詢法律 {$law_id} 的進度");

            $logs = $res->歷程;
            $logs = array_filter($logs, function ($log) use ($version_date) {
                if (strpos($log->id, '三讀-') !== 0) {
                    return false;
                }
                $log_date = explode('-', $log->id, 2)[1] ?? NULL;
                return 7 * 86400 > abs(strtotime($log_date) - strtotime($version_date));
            });

            $logs = array_values($logs);
            if (!empty($logs)) {
                $histories = $logs[0]->bill_log ?? [];
                $version_selected = new StdClass;
                $version_selected->日期 = $version_date;
                $version_selected->動作 = "修正";
                $versions_data->warning = 'history-from-progress';
            }
        }
        if (isset($histories)) {
            $version_selected->歷程 = [
                (object) [
                    'id' => $version_id_selected,
                    'bill_log' => $histories,
                ],
            ];
        }

        //$version_in_terms need to be build when law is on draft
        $is_draft = is_null($versions_in_terms);
        if ($is_draft) {
          $term_dates = LyDateHelper::$term_dates;
          $versions_in_terms = array_fill_keys(array_keys($term_dates), []);
        }

        foreach ($versions_in_terms as $term => $versions) {
            $version_id = "{$law_id}:{$term}-progress";
            $version = (object) [
                '版本編號' => $version_id,
            ];
            if (is_null($version_id_selected) and $version_id_input == $version_id) {
                $res = LYAPI::apiQuery("/law/{$law_id}/progress?屆={$term}", "查詢 law_id: {$law_id} 第 {$term} 屆 progress");
                $history_groups = $res->歷程;

                //去掉三讀的 bill_log (跟 version 重複)
                //尚未有正式三讀資料前則保留三讀
                $history_groups = array_filter($history_groups, function ($history_group) use ($latest_third_reading_date) {
                    $id = $history_group->id;
                    if (mb_strpos($id, '三讀') === 0) {
                        $date = mb_substr($id, 3);
                        return $date > $latest_third_reading_date;
                    }
                    return true;
                });

                $version->歷程 = $history_groups;
                $version_selected = $version;
                $version_id_selected = $version_id;
                $term_selected = $term;
            }
            $versions_in_terms[$term][] = $version;
        }

        //Default query progress at latest term when law is on draft
        if ($is_draft) {
            $latest_term = reset($versions_in_terms);
            $version_id_selected = $latest_term[0]->版本編號;
            $term = explode('-', explode(':', $version_id_selected)[1])[0];
            $res = LYAPI::apiQuery("/law/{$law_id}/progress?屆={$term}", "查詢 law_id: {$law_id} 第 {$term} 屆 progress");
            $history_groups = $res->歷程;

            //去掉三讀的 bill_log (跟 version 重複)
            //尚未有正式三讀資料前則保留三讀
            $history_groups = array_filter($history_groups, function ($history_group) use ($latest_third_reading_date) {
                $id = $history_group->id;
                if (mb_strpos($id, '三讀') === 0) {
                    $date = mb_substr($id, 3);
                    return $date > $latest_third_reading_date;
                }
                return true;
            });

            $version->歷程 = $history_groups;
            $version_selected = $version;
            $term_selected = $term;
        }

        //repack versions_data
        $versions_data->versions_in_terms = $versions_in_terms;
        $versions_data->version_selected = $version_selected;
        $versions_data->version_id_selected = $version_id_selected;
        $versions_data->term_selected = $term_selected;

        return $versions_data;
    }

    public static function getMinguoDate($version_date)
    {
        $is_valid = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $version_date) !== false);
        if (!$is_valid) {
            return $version_date;
        }
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
