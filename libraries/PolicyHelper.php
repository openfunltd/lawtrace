<?php

class PolicyHelper
{
    public static function addToHistoryGroup($law_id, $term, $history_groups)
    {
        //搜尋部預告版 by law_id
        $res = PolicyAPI::apiQuery("/policy/bylaw/{$law_id}", "依法律{$law_id}查詢部預告版");
        $policies = $res->policies;
        //根據當前選擇的 term 剔除掉 隨後屆期的部預告版 與 太久遠的部預告版（暫定一年以上算是太久遠)
        $term_start_date = LyDateHelper::$term_dates[$term][0];
        $term_end_date = LyDateHelper::$term_dates[$term][1];
        $policy_start_date = date("Y-m-d", strtotime("-1 year", strtotime($term_start_date)));
        $policies = array_filter($policies, function($policy) use ($policy_start_date, $term_end_date){
            $publish_date = $policy->發布日期;
            if ($policy_start_date <= $publish_date and $publish_date <= $term_end_date) {
                return true;
            }
            return false;
        });
        $policies = array_map(function ($policy) use ($term_start_date) {
            $policy->is_in_term = ($term_start_date <= $policy->發布日期);
            return $policy;
        }, $policies);

        $policy_log = [];

        //format policy such that fronend can render data
        foreach ($policies as $policy) {
            $policy_formatted = new stdClass();
            $policy_formatted->主提案 = $policy->主協辦單位 . '部預告';
            $policy_formatted->會議日期 = $policy->發布日期;
            $policy_formatted->會議民國日期 = LawHistoryHelper::getMinguoDateFormat2($policy->發布日期);
            $policy_formatted->會議民國日期v2 = LawHistoryHelper::getMinguoDateFormat3($policy->發布日期);
            //withdraw_status
            //bill_id
            $policy_formatted->policy_uid = $policy->policy_uid;
            //proposers_str
            //article_numbers
            //review_ppg_url
            $policy_formatted->policy_url = "https://join.gov.tw/policies/detail/{$policy->policy_uid}";
            $policy_log[] = $policy_formatted;
        }

        //case: 未議決議案:僅未審查一種分類
        if (count($history_groups) == 1 and $history_groups[0]->id == '未分類') {
            $merged = array_merge($history_groups[0]->bill_log, $policy_log);
            usort($merged, function($bill_a, $bill_b) {
                return $bill_a->會議日期 <=> $bill_b->會議日期;
            });
            $history_groups[0]->bill_log = $merged;
        }

        return $history_groups;
    }
}
