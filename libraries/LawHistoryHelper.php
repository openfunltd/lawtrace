<?php

class LawHistoryHelper
{
    public static function getDetailedHistories($histories)
    {
        //histories order by date ASC
        usort($histories, function($h1, $h2) {
            $date_h1 = $h1->會議日期 ?? '';
            $date_h2 = $h2->會議日期 ?? '';
            return $date_h1 <=> $date_h2;
        });

        foreach ($histories as $history) {
            $related_doc = $history->關係文書 ?? [];
            $related_doc = $related_doc[0] ?? new stdClass();
            $bill_id = $related_doc->billNo ?? null;
            $date = $history->會議日期;
            $history->會議民國日期 = self::getMinguoDateFormat2($date);

            if (isset($bill_id)) {
                $res = LYAPI::apiQuery("/bill/{$bill_id}","查詢提案詳細資訊 bill_id: {$bill_id}");
                $res_error = $res->error ?? true;
                if (!$res_error) {
                    $history->bill_id = $bill_id;
                    $bill = $res->data;
                }
            }

            if (!isset($bill_id) or !isset($bill)) {
                continue;
            }

            //get proposer or progress title
            $proposer = $bill->{'提案單位/提案委員'} ?? '';
            $proposer = self::trimProposer($proposer);
            $history->proposers_str = $proposer;

            //determine party image
            $party_img_path = PartyHelper::getImage($proposer);
            $term = $bill->屆 ?? 0;
            $proposers = $bill->提案人 ?? [];
            $leading_proposer = $proposers[0] ?? NULL;
            if (is_null($party_img_path) and $term != 0 and isset($leading_proposer)) {
                $res = LYAPI::apiQuery(
                    "/legislator/{$term}-{$leading_proposer}",
                    "查詢 {$term}-{$leading_proposer} 黨籍"
                );
                $res_error = $res->error ?? true;
                if (!$res_error) {
                    $legislator = $res->data;
                    $party = $legislator->黨籍;
                    $party_img_path = PartyHelper::getImage($party);
                }
            }
            if (isset($party_img_path)) {
                $history->party_img_path = $party_img_path;
            }

            //取得條號 Array: 第一條, 第二十條 => [1, 20]
            $bill_source = $bill->提案來源 ?? '';
            $amendment = $bill->對照表 ?? [];
            $amendment = $amendment[0] ?? new stdClass();
            if (!empty((array)$amendment)) {
                $article_numbers = self::getArticleNumbers($amendment);
                $history->article_numbers = $article_numbers;
            }

            //議案詳細資訊連結到議事公報網
            $ppg_url = $bill->url ?? '';
            if ($ppg_url != '') {
                $history->ppg_url = $ppg_url;
            }
        }

        return $histories;
    }

    private static function trimProposer($proposer)
    {
        $redundant_texts = ['本院委員', '本院'];
        foreach ($redundant_texts as $redundant_text) {
            if (mb_strpos($proposer, $redundant_text) === 0) {
                $proposer = mb_substr($proposer, mb_strlen($redundant_text));
            }
        }
        return $proposer;
    }

    private static function getArticleNumbers($amendment)
    {
        $type = $amendment->立法種類;
        $rows = $amendment->rows;
        $key = '修正'; // if type == '修正條文'
        if ($type == '增訂條文') {
            $key = '增訂';
        }
        $article_numbers = array_map(function($row) use ($key) {
            $text = $row->{$key};
            $text = mb_ereg_replace('　', ' ', $text);
            $article_number = explode(' ', $text)[0];
            $article_number = mb_ereg_replace('第', '', $article_number);
            $article_number = mb_ereg_replace('條', '', $article_number);
            $article_number_arr = explode('之', $article_number);
            foreach ($article_number_arr as $idx => $number) {
                $article_number_arr[$idx] = NumeralTransformHelper::zhtwToArabic($number);
            }
            $article_number = implode('-', $article_number_arr);
            return $article_number;
        }, $rows);

        //filter out chapters
        //TODO 確認是否要呈現修改章節名稱
        $article_numbers = array_filter($article_numbers, function($article_number) {
            $chapter_units = ['篇', '章', '節', '款', '目'];
            foreach ($chapter_units as $unit) {
                if (mb_strpos($article_number, $unit) !== false) {
                    return false;
                }
            }
            return true;
        });

        return $article_numbers;
    }

    public static function groupByTimeline($histories)
    {
        $timeline = [];
        foreach ($histories as $history) {
            $minguo_date = $history->會議民國日期;
            $progress = $history->進度;
            $need_new_segment = true;
            foreach ($timeline as $timeline_segment) {
                if ($minguo_date == $timeline_segment->會議民國日期 and $progress == $timeline_segment->進度) {
                    $timeline_segment->items[] = $history;
                    $need_new_segment = false;
                    break;
                }
            }
            if ($need_new_segment) {
                $timeline[] = (object) [
                    '會議民國日期' => $minguo_date,
                    '進度' => $progress,
                    'items' => [
                        $history,
                    ],
                ];
            }
        }
        return $timeline;
    }

    public static function getMinguoDateFormat2($version_date)
    {
        [$year, $month, $day] = explode('-', $version_date);
        $minguo = intval($year) - 1911;
        return "{$minguo}/{$month}/{$day}";
    }
}
