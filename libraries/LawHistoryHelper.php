<?php

include_once(__DIR__ . '/LyTcToolkit.php');

class LawHistoryHelper
{
    public static function updateDetails($history_groups, $term_selected)
    {
        //get legislators' data for checking party later
        $res = LYAPI::apiQuery(
            "/legislators?屆={$term_selected}&limit=300",
            "查詢第 {$term_selected} 屆立委基本資料（主要查詢黨籍）"
        );
        $legislators = $res->legislators ?? [];

        $history_groups = self::removeUngroupedMeet($history_groups);
        $history_groups = self::updateBillDetails($history_groups, $legislators);
        $history_groups = self::updateMeetDetails($history_groups, $legislators);
        $history_groups = self::updateGroupMetadata($history_groups);
        $history_groups = self::updateIncidentalResolution($history_groups);
        $history_groups = self::groupByTimeline($history_groups);
        $history_groups = self::orderGroups($history_groups);

        return $history_groups;
    }

    private static function removeUngroupedMeet($history_groups)
    {
        //去除「未分類」的 progress 中的審查會議資料
        foreach ($history_groups as $group) {
            if ($group->id == '未分類') {
                $group->bill_log = array_filter($group->bill_log, function ($progress) {
                    return mb_strpos($progress->進度, '審查') === false;
                });
            }
        }
        return $history_groups;
    }

    //Incidental Resolution = 附帶決議
    private static function updateIncidentalResolution($history_groups)
    {
        foreach ($history_groups as $history_group) {
            $histories = $history_group->bill_log;
            foreach ($histories as $history) {
                if ($history->進度 == '附帶決議') {
                    $gazette_id = $history->公報編號 ?? null;
                    if (is_null($gazette_id)) {
                        continue;
                    }
                    if (mb_substr($gazette_id, -2) === '00') {
                        $gazette_id = mb_substr($gazette_id, 0, -2) . '01';
                    }
                    $terms = self::getTermsFromGazetteId($gazette_id);
                    $gazette_ppg_url = sprintf('https://ppg.ly.gov.tw/ppg/publications/official-gazettes/%02d/%02d/%02d/details',
                        $terms[0],
                        $terms[1],
                        $terms[2]
                    );
                    $history->gazette_ppg_url = $gazette_ppg_url;
                    $history->is_incidental_resolution = true;
                }
            }
        }
        return $history_groups;
    }

    private static function orderGroups($history_groups)
    {
        //order by date
        usort($history_groups, function ($groupA, $groupB) {
            if ($groupA->id === '未分類' or $groupB->id === '未分類') {
                return 0;
            }
            $id_arrayA = explode('-', $groupA->id);
            $id_arrayB = explode('-', $groupB->id);
            $dateA = sprintf('%d-%d-%d', $id_arrayA[1], $id_arrayA[2], $id_arrayA[3]);
            $dateB = sprintf('%d-%d-%d', $id_arrayB[1], $id_arrayB[2], $id_arrayB[3]);
            return $dateA <=> $dateB;
        });

        //把 id = '未分類'放到最後面
        usort($history_groups, function ($groupA, $groupB) {
            return ($groupA->id === '未分類') - ($groupB->id === '未分類');
        });

        //order bills in history_group by date for id = '未分類'
        foreach ($history_groups as $history_group) {
            if ($history_group->id != '未分類') {
                continue;
            }
            usort($history_group->bill_log, function ($billA, $billB) {
                return $billA->會議日期 <=> $billB->會議日期;
            });
        }

        return $history_groups;
    }

    private static function updateGroupMetadata($history_groups)
    {
        foreach ($history_groups as $history_group) {
            $histories = $history_group->bill_log;
            $id = $history_group->id ?? null;
            //不處理三讀版本的 group metadata
            if (is_null($id) or $id == '未分類' or mb_strpos($id, ':') !== false) {
                continue;
            }
            $proposers = [];
            foreach ($histories as $history) {
                $proposer = $history->主提案 ?? null;
                if (isset($proposer)) {
                    $proposers[] = $proposer;
                }
            }
            if (count($proposers) >= 3) {
                $history_group->group_title = $proposers[0] . '、' . $proposers[1] . '等 ' . count($proposers) . ' 版本';
            } elseif (count($proposers) < 3) {
                $history_group->group_title = implode('、', $proposers) . '提案版本';
            }
            $id_details = explode('-', $id);
            $history_group->review_date = sprintf('%d年%d月%d日%s',
                intval($id_details[1]) - 1911,
                $id_details[2],
                $id_details[3],
                $id_details[0],
            );
            $history_group->compare_url = "/law/compare?source=bill:{$id_details[4]}";
            $history_group->review_ppg_url = "https://ppg.ly.gov.tw/ppg/bills/{$id_details[4]}/details";
        }

        return $history_groups;
    }

    private static function updateBillDetails($history_groups, $legislators)
    {
        //batch retrieve bills within histories
        $bill_ids = [];
        foreach ($history_groups as $history_group) {
            $histories = $history_group->bill_log;
            foreach ($histories as $history) {
                $related_doc = $history->關係文書 ?? [];
                if (is_array($related_doc)) {
                    $related_doc = $related_doc[0] ?? new stdClass();
                }
                $bill_id = $related_doc->billNo ?? null;
                if (isset($bill_id)) {
                    $bill_ids[] = $bill_id;
                }
            }
        }
        $bill_ids = array_unique($bill_ids);

        //get bills' data within one query
        $output_fields = [
            '提案單位/提案委員',
            '提案人',
            '屆',
            '提案來源',
            '對照表',
        ];
        $url = sprintf('/bills?output_fields=%s&議案編號=%s',
            implode('&output_fields=', $output_fields),
            implode('&議案編號=', $bill_ids)
        );
        $res = LYAPI::apiQuery($url, "整批查詢提案詳細資訊");
        $res_total = $res->total ?? 0;
        $bills = ($res_total > 0) ? $res->bills : [];
        $withdraw_texts = ['撤案', '同意撤回'];

        //enrich history data with bill data
        foreach ($history_groups as $history_group) {
            $histories = $history_group->bill_log;
            foreach ($histories as $history) {
                $related_doc = $history->關係文書 ?? [];
                if (is_array($related_doc)) {
                    $related_doc = $related_doc[0] ?? new stdClass();
                }
                $bill_id = $related_doc->billNo ?? null;
                $date = $history->會議日期;
                $history->會議民國日期 = self::getMinguoDateFormat2($date);
                $history->會議民國日期v2 = self::getMinguoDateFormat3($date);
                $history->compare_url = "/law/compare?source=bill:{$bill_id}";
                $history->review_ppg_url = "https://ppg.ly.gov.tw/ppg/bills/{$bill_id}/details";

                //check is withdrew
                $latest_status = $history->該提案最新狀態 ?? null;
                $progress_status = $history->進度 ?? null;
                $history->withdraw_status = '';
                if (in_array($latest_status, $withdraw_texts)) {
                    $history->withdraw_status = $latest_status;
                }
                if (in_array($progress_status, $withdraw_texts)) {
                    $history->withdraw_status = $progress_status;
                }

                //filter to get desired bill data
                $bill_filtered = array_filter($bills, function($bill) use ($bill_id) {
                    return $bill->議案編號 === $bill_id;
                });
                if (!empty($bill_filtered)) {
                    $bill = reset($bill_filtered);
                    $history->bill_id = $bill_id;
                }

                //如果沒有對應的 bill 資料，會依現有的資料盡量去呈現資料: 黨籍、提案下載連結
                if (!isset($bill_id) or !isset($bill)) {
                    //party
                    if (property_exists($history, '主提案')) {
                        $leading_proposer = $history->主提案;
                        $legislators_filtered = array_filter($legislators, function ($legislator) use ($leading_proposer) {
                            return $legislator->委員姓名 == $leading_proposer;
                        });
                        $legislator = reset($legislators_filtered);
                        $party = $legislator->黨籍 ?? null;
                        $party_img_path = PartyHelper::getImage($party);
                        if (isset($party_img_path)) {
                            $history->party_img_path = $party_img_path;
                        }
                    }
                    //提案關係文書
                    $related_doc_type = $related_doc->類型 ?? '';
                    if ($related_doc_type == '提案') {
                        $history->related_doc_url = $related_doc->連結;
                    }
                    continue;
                }

                //get proposer or progress title
                $proposer = $bill->{'提案單位/提案委員'} ?? '';
                $proposer = self::trimProposer($proposer);
                $history->proposers_str = $proposer;

                //determine party image
                $party_img_path = PartyHelper::getImage($proposer);
                $proposers = $bill->提案人 ?? [];
                $leading_proposer = $proposers[0] ?? null;
                if (is_null($party_img_path) and isset($leading_proposer)) {
                    $legislators_filtered = array_filter($legislators, function ($legislator) use ($leading_proposer) {
                        return $legislator->委員姓名 == $leading_proposer;
                    });
                    $legislator = reset($legislators_filtered);
                    $party = $legislator->黨籍 ?? null;
                    $party_img_path = PartyHelper::getImage($party);
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
        }

        return $history_groups;
    }

    private static function updateMeetDetails($history_groups, $legislators)
    {
        //get committees' data for later use
        $res = LYAPI::apiQuery("/committees?page=1&per_page=20", "查詢各委員會基本資料");
        $committees = $res->committees ?? [];
        $committees = array_filter($committees, function($committee) {
            return $committee->委員會類別 != 3;
        });

        //batch retrieve gazettes with meet and meet_ids within histories
        $meet_ids = [];
        $gazette_ids = [];
        foreach ($history_groups as $history_group) {
            $histories = $history_group->bill_log;
            foreach ($histories as $history) {
                $needles = ['二讀', '三讀', '委員會', '黨團協商'];
                $progress_status = $history->進度 ?? '';
                $is_meet = false;
                foreach ($needles as $needle) {
                    if (mb_strpos($progress_status, $needle) !== false) {
                        $is_meet = true;
                        break;
                    }
                }
                $meet_id = $history->會議代碼 ?? null;
                $gazette_id = $history->公報編號 ?? null;
                $related_docs = $history->關係文書 ?? null;
                if (is_object($related_docs)) {
                    $related_docs = [$related_docs];
                }
                if (mb_substr($gazette_id, -2) === '00') {
                    $gazette_id = mb_substr($gazette_id, 0, -2) . '01';
                }

                if ($is_meet) {
                    if (isset($meet_id)) {
                        $meet_ids[] = $meet_id;
                        $history->meet_id = $meet_id;
                    }

                    if (isset($gazette_id)) {
                        $gazette_ids[] = $gazette_id;
                        $history->gazette_id = $gazette_id;
                        $terms = self::getTermsFromGazetteId($gazette_id);
                        $gazette_ppg_url = sprintf('https://ppg.ly.gov.tw/ppg/publications/official-gazettes/%02d/%02d/%02d/details',
                            $terms[0],
                            $terms[1],
                            $terms[2]
                        );
                        $history->gazette_ppg_url = $gazette_ppg_url;
                    }

                    if (isset($related_docs)) {
                        foreach ($related_docs as $related_doc) {
                            if ($related_doc->類型 == '審查報告') {
                                $history->review_report_doc = $related_doc->連結;
                                break;
                            }
                        }
                    }
                }

                $history->is_meet = $is_meet;
            }
        }

        //get gazette_agendas' data within one query
        $url = sprintf('/gazette_agendas?公報編號=%s',
            implode('&公報編號=', $gazette_ids)
        );
        $res = LYAPI::apiQuery($url, "整批查詢公報章節");
        $res_total = $res->total ?? 0;
        $gazette_agendas = ($res_total > 0) ? $res->gazetteagendas : [];

        //retrieve meet data within gazette_agenda (only for 委員會)
        foreach ($history_groups as $history_group) {
            $histories = $history_group->bill_log;
            foreach ($histories as $history) {
                if (mb_strpos($history->進度, '委員會') === false or !property_exists($history, 'gazette_id')) {
                    continue;
                }
                $gazette_id = $history->gazette_id;
                $gazette_agenda_pages = explode(' ', $history->立法紀錄)[1];
                $gazette_agenda_start_page = explode('-', $gazette_agenda_pages)[0];
                foreach ($gazette_agendas as $agenda) {
                    if ($agenda->公報編號 == $gazette_id and
                        $agenda->起始頁碼 <= $gazette_agenda_start_page and
                        $agenda->結束頁碼 >= $gazette_agenda_start_page) {

                        $agenda_committees_str = mb_substr($agenda->案由, 0, mb_strpos($agenda->案由, '委員會'));
                        $agenda_committees = explode('、', $agenda_committees_str);

                        $meet_committees = [];
                        $committee_ids = [];
                        foreach ($agenda_committees as $order_idx => $agenda_committee) {
                            foreach ($committees as $committee) {
                                $committee_name = str_replace('委員會', '', $committee->委員會名稱);
                                $matched_position = mb_strpos($agenda_committee, $committee_name);
                                if ($matched_position !== false) {
                                    $committee_ids[] = $committee->委員會代號;
                                    $meet_committees[] = $committee_name;
                                    break;
                                }
                            }
                        }

                        $meet_id = sprintf("%s-%d-%d-%s-%d",
                            (count($committee_ids) > 1) ? '聯席會議' : '委員會',
                            $agenda->屆,
                            $agenda->會期,
                            implode(',', $committee_ids),
                            $agenda->會次,
                        );
                        $meet_ids[] = $meet_id;
                        if (mb_strpos($history->進度, '公聽會') === false) { //排除進度：委員會(公聽會) 的狀況
                            $history->meet_id = $meet_id;
                        }
                        $history->meet_committees = $agenda_committees;
                        break;
                    }
                }
            }
        }

        $meet_ids = array_unique($meet_ids);

        //get meets' data within one query
        $url = sprintf('/meets?會議代碼=%s', implode('&會議代碼=', $meet_ids));
        $res = LYAPI::apiQuery($url, "整批查詢會議資料");
        $res_total = $res->total ?? 0;
        $meets = ($res_total > 0) ? $res->meets : [];

        //enrich history data with meet data
        foreach ($history_groups as $history_group) {
            $histories = $history_group->bill_log;
            foreach ($histories as $history) {
                if (!($history->is_meet)) {
                    continue;
                }
                foreach ($meets as $meet) {
                    if ($meet->會議代碼 == $history->meet_id) {
                        //flatten meet related data into history(object)
                        $meet_data = $meet->會議資料 ?? [];
                        foreach ($meet_data as $single_date_meet_data) {
                            if ($single_date_meet_data->日期 == $history->會議日期) {
                                //get ppg_url
                                $history->ppg_url = $single_date_meet_data->ppg_url;

                                //get convener(召委) and it's party
                                $convener = $single_date_meet_data->委員會召集委員 ?? null;
                                if (isset($convener)) {
                                    $convener = str_replace('委員', '', $convener);
                                    $legislators_filtered = array_filter($legislators, function ($legislator) use ($convener) {
                                        return $legislator->委員姓名 == $convener;
                                    });
                                    $legislator = reset($legislators_filtered);
                                    if ($legislator !== false) {
                                        $party = $legislator->黨籍;
                                        $party_img_path = PartyHelper::getImage($party);
                                    }
                                    $history->convener = $convener;
                                    $history->convener_party_img_path = $party_img_path;
                                }
                                break;
                            }
                        }
                        break;
                    }
                }
            }
        }

        return $history_groups;
    }

    public static function trimProposer($proposer)
    {
        $redundant_texts = ['本院委員', '本院'];
        foreach ($redundant_texts as $redundant_text) {
            if (mb_strpos($proposer, $redundant_text) === 0) {
                $proposer = mb_substr($proposer, mb_strlen($redundant_text));
            }
        }
        return $proposer;
    }

    public static function getArticleNumbers($amendment)
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
            $article_number = mb_ereg_replace('章', '', $article_number);
            $article_number_arr = explode('之', $article_number);
            foreach ($article_number_arr as $idx => $number) {
                try {
                    $article_number_arr[$idx] = LyTcToolkit::parseNumber($number);
                } catch (Exception $e) {
                    return '';
                }
            }
            $article_number = implode('-', $article_number_arr);
            return $article_number;
        }, $rows ?? []);

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

    private static function groupByTimeline($history_groups)
    {
        foreach ($history_groups as $history_group) {
            $histories = $history_group->bill_log;
            $timeline = [];
            foreach ($histories as $history) {
                $minguo_date = $history->會議民國日期;
                $progress = $history->進度;
                $need_new_item = true;
                foreach ($timeline as $timeline_item) {
                    if ($minguo_date == $timeline_item->會議民國日期 and $progress == $timeline_item->進度) {
                        $timeline_item->items[] = $history;
                        $need_new_item = false;
                        break;
                    }
                }
                if ($need_new_item) {
                    $timeline[] = (object) [
                        '會議民國日期' => $minguo_date,
                        '進度' => $progress,
                        'items' => [
                            $history,
                        ],
                    ];
                }
            }
            $history_group->timeline = $timeline;
        }
        return $history_groups;
    }

    public static function getMinguoDateFormat2($version_date)
    {
        [$year, $month, $day] = explode('-', $version_date);
        $minguo = intval($year) - 1911;
        return "{$minguo}/{$month}/{$day}";
    }

    public static function getMinguoDateFormat3($version_date)
    {
        [$year, $month, $day] = explode('-', $version_date);
        $minguo = intval($year) - 1911;
        return "{$minguo}年{$month}月{$day}日";
    }

    public static function getTermsFromGazetteId($gazette_id)
    {
        $terms = [];
        // 年
        if (strpos(strval($gazette_id), '1') === 0) {
            $terms[] = substr($gazette_id, 0, 3);
            $gazette_id = substr($gazette_id, 3);
        } else {
            $terms[] = substr($gazette_id, 0, 2);
            $gazette_id = substr($gazette_id, 2);
        }

        // 期
        $terms[] = substr($gazette_id, 0, strlen($gazette_id) - 2);

        // 冊
        $terms[] = substr($gazette_id, -2);
        return $terms;

    }
}
