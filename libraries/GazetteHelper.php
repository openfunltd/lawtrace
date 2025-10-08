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
    }
}
