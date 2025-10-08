<?php

class GazetteHelper
{
    public static function linkAgendaId($histories)
    {
        $linked_data = [];
        foreach ($histories as $history) {
            if ($history->進度 == '委員會審查') {
                $data = (object) [];
                $data->key = $history->立法紀錄; 
                $data = self::decomposeKey($data);
                $linked_data[] = $data;
            }
        }
        $linked_data = self::queryAgendas($linked_data);
        foreach ($histories as $history) {
            foreach ($linked_data as $data) {
                if ($history->立法紀錄 == $data->key) {
                    $history->公報議程編號 = $data->公報議程編號;
                }
            }
        }

        return $histories;
    }

    private static function decomposeKey($data)
    {
        //decompose
        $key = $data->key;
        $key = preg_replace('/\s+/u', '', $key); //remove white space
        preg_match('/(\d+)卷(\d+)期/u', $key, $matches);
        $scroll = (int) $matches[1];
        $issue = (int) $matches[2];
        $volume = 1;
        if (preg_match('/號(.)冊/u', $key, $matches)) {
            $volume = LyTcToolkit::parseNumber($matches[1]);
        }
        preg_match('/[號冊](.*)頁/u', $key, $matches); 
        $page_str = $matches[1];
        $pages = explode('-', $page_str);
        $page_start = $pages[0];
        $page_end = end($pages);

        //fill-in
        $data->scroll = $scroll;
        $data->issue = $issue;
        $data->volume = $volume;
        $data->page_start = $page_start;
        $data->page_end = $page_end;

        return $data;
    }

    private static function queryAgendas($linked_data)
    {
        //gather variables in query string
        $scrolls = [];
        $issues = [];
        $volumes = [];
        foreach ($linked_data as $data) {
            if (!in_array($data->scroll, $scrolls)) {
                $scrolls[] = $data->scroll;
            }
            if (!in_array($data->issue, $issues)) {
                $issues[] = $data->issue;
            }
            if (!in_array($data->volume, $volumes)) {
                $volumes[] = $data->volume;
            }
        }

        //build query string
        $url = sprintf("/gazette_agendas?%s&%s&%s&limit=1000",
            '卷=' . implode('&卷=', $scrolls),
            '期=' . implode('&期=', $issues),
            '冊別=' . implode('&冊別=', $volumes)
        );

        $ret = LYAPI::apiQuery($url, "整批查詢公報議程編號（gazette_agenda_ids）");

        //get matched gazette agenda
        $agendas = $ret->gazetteagendas;
        foreach ($linked_data as $data) {
            foreach ($agendas as $agenda) {
                if (
                    $agenda->卷 == $data->scroll and $agenda->期 == $data->issue and $agenda->冊別 == $data->volume and
                    $agenda->起始頁碼 <= $data->page_start and $data->page_end <= $agenda->結束頁碼
                ) {
                    $data->agenda = $agenda;
                    $data->公報議程編號 = $agenda->公報議程編號;
                    break;
                }
            }
        }

        return $linked_data;
    }
}
