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
                    $proposer = $bill->{'提案單位/提案委員'} ?? '';
                    $proposer = self::trimProposer($proposer);
                    $history->proposer_or_progress = $proposer;
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
                }
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
}
