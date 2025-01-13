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
            $history->proposer_or_progress = $history->進度;

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
            $history->proposer_or_progress = $proposer;

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
            if ($bill_source == '委員提案' and !empty((array)$amendment)) {
                $article_numbers = self::getArticleNumbers($amendment);
                $history->article_numbers = $article_numbers;
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

        return $article_numbers;
    }
}
