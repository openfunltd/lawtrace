<?php

class GazetteHelper
{
    public static function getAgendaData($gazette_str)
    {
        preg_match('/^\d+/', $gazette_str, $matches);
        $year = $matches[0] ?? null;

        preg_match('/卷(\d+)/', $gazette_str, $matches);
        $issue = $matches[1] ?? null;
        $issue = intval($issue);

        $volume = 1;
        $page_start_idx = mb_strpos($gazette_str, '號');
        if (str_contains($gazette_str, '冊')) {
            $start_idx = mb_strpos($gazette_str, '號');
            $end_idx = mb_strpos($gazette_str, '冊');
            $page_start_idx = $end_idx;
            $volume_zh = mb_substr($gazette_str, $start_idx + 1, $end_idx - ($start_idx + 1));
            $volume = array_search($volume_zh, self::$zhNumbers, true) + 1;
        }
        $padded_volume = str_pad($volume, 2, '0', STR_PAD_LEFT);

        $page_str = mb_substr($gazette_str, $page_start_idx + 1);
        $page_str = mb_ereg_replace('頁', '', $page_str);
        $page_str = mb_ereg_replace(' ', '', $page_str);
        $pages = explode('-', $page_str);
        $page_start = intval($pages[0]);
        $page_end = intval($pages[1]);

        $gazette_id = "{$year}{$issue}{$padded_volume}";
        $gazette_url = "https://dataly.openfun.app/collection/item/gazette/{$gazette_id}";
        $res = LYAPI::apiQuery("/gazette_agendas?公報編號={$gazette_id}", '查詢關連到的公報章節');
        if ($res->total == 0) {
            return [$gazette_url, null];
        }
        $agendas = $res->gazetteagendas;
        foreach ($agendas as $agenda) {
            $agenda_year = $agenda->卷;
            $agenda_issue = $agenda->期;
            $agenda_volume = $agenda->冊別;
            if ($agenda_year != $year or $agenda_issue != $issue and $agenda_volume != $volume) {
                continue;
            }
            $lower_bound = $agenda->起始頁碼; 
            $upper_bound = $agenda->結束頁碼; 
            if ($lower_bound <= $page_start and $page_end <= $upper_bound) {
                $agenda_id = $agenda->公報議程編號;
                return [$gazette_url, "https://dataly.openfun.app/collection/item/gazette_agenda/{$agenda_id}/content"];
            }
        }
        return [$gazette_url, null];
    }

    private static $zhNumbers = [
        '一', '二', '三', '四', '五', '六', '七', '八', '九', '十',
        '十一', '十二', '十三', '十四', '十五', '十六', '十七', '十八', '十九', '二十',
        '二十一', '二十二', '二十三', '二十四', '二十五', '二十六', '二十七', '二十八', '二十九', '三十',
        '三十一', '三十二', '三十三', '三十四', '三十五', '三十六', '三十七', '三十八', '三十九', '四十',
    ];
}
