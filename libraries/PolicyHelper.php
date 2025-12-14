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
            $compare_url = sprintf("https://%s/law/compare?source=join-policy:%s:%s",
                $_SERVER['HTTP_HOST'],
                $policy->policy_uid,
                $law_id
            );
            $policy_formatted->compare_url = $compare_url;
            $policy_formatted->policy_url = "https://join.gov.tw/policies/detail/{$policy->policy_uid}";
            $policy_log[] = $policy_formatted;
        }

        //非未審查的分類中，有院版的 bill，納入鏈結的部預告版進入分類中
        foreach ($history_groups as $group_key => $history_group) {
            if ($history_group->id == '未分類') continue;
            foreach ($history_group->bill_log  as $bill) {
                if (mb_strpos($bill->主提案, '行政院') === false) continue;
                $res = PolicyAPI::apiQuery("/policy/bybill/{$law_id}/{$bill->bill_id}", "依法律{$law_id}查詢部預告版");
                $linked_policies = $res->policies;
                $linked_policy_uids = array_map(fn($linked_policy) => $linked_policy->policy_uid, $linked_policies);
                foreach ($policy_log as $key => $policy) {
                    if (($matched_key = array_search($policy->policy_uid, $linked_policy_uids)) === false) {
                        $policy_log[$key]->is_linked = false;
                        continue;
                    }
                    $policy_log[$key]->is_linked = true;
                    //TODO list article_nums of policy
                    $policy_log[$key]->article_numbers = self::getArticleNums($linked_policies[$matched_key]->對照表);

                    //insert join policy into timeline
                    $history_groups[$group_key]->timeline[] = (object) [
                        '會議民國日期' => $policy->會議民國日期,
                        '進度' => '部預告版發布',
                        'items' => [$policy],
                    ];
                    usort($history_groups[$group_key]->timeline, function($tl_a, $tl_b) {
                        return $tl_a->會議民國日期 <=> $tl_b->會議民國日期;
                    });
                }
            }
        }
        $policy_log = array_filter($policy_log, fn($policy) => !$policy->is_linked);

        //剩下的 policies 放進未審查分類中
        foreach ($history_groups as $key => $history_group) {
            if ($history_group->id != '未分類') continue;
            $merged = array_merge($history_group->bill_log, $policy_log);
            usort($merged, function($bill_a, $bill_b) {
                return $bill_a->會議日期 <=> $bill_b->會議日期;
            });
            $history_groups[$key]->bill_log = $merged;
        }

        return $history_groups;
    }

    public static function getArticleNums($amendment_table)
    {
        $key = (property_exists($amendment_table[0], '修正')) ? '修正' : '條文';

        $article_nums = array_map(function($row) use ($key) {
            $text = $row->{$key};
            $text = mb_ereg_replace('　', ' ', $text);
            $article_num = explode(' ', $text)[0];

            //多檢查 start index
            $start_idx = mb_strpos($article_num, '第');
            $article_num = mb_substr($article_num, $start_idx);

            $article_num = mb_ereg_replace('第', '', $article_num);
            $article_num = mb_ereg_replace('條', '', $article_num);
            $article_num = mb_ereg_replace('章', '', $article_num);
            $article_num_arr = explode('之', $article_num);
            foreach ($article_num_arr as $idx => $number) {
                try {
                    $article_num_arr[$idx] = LyTcToolkit::parseNumber($number);
                } catch (Exception $e) {
                    return '';
                }
            }
            $article_num = implode('-', $article_num_arr);
            return $article_num;
        }, $amendment_table ?? []);

        //filter out chapters
        //TODO 確認是否要呈現修改章節名稱
        $article_nums = array_filter($article_nums, function($article_num) {
            $chapter_units = ['篇', '章', '節', '款', '目'];
            foreach ($chapter_units as $unit) {
                if (mb_strpos($article_num, $unit) !== false) {
                    return false;
                }
            }
            return true;
        });

        return $article_nums;
    }
}
