<?php

include(__DIR__ . '/LyTcToolkit.php');

class DiffHelper
{
    public static function getBillNosFromSource($id)
    {
        // 可來自 會議、審查報告、三讀版本
        //   會議：meet:{meet_id}:{law_id} Ex: meet:委員會-11-2-23-20:02017 
        //     因為一場會議可能有多個法案，需指定 law_id ，會抓取該會議的相關議案
        //   審查報告：bill:{billNo} Ex: bill:203110083270000
        //     審查報告會有關係議案
        //   三讀版本：version:{law_id}:{date} Ex: version:01254:2024-12-31
        //     三讀版本也會有完整關係議案
        $terms = explode(':', $id);
        $type = $terms[0];
        $billNos = [];
        if ($type == 'meet') {
            $meet_id = $terms[1];
            if (!($terms[2] ?? false)) {
                throw new Exception("meet_id 必須指定 law_id");
            }
            $law_id = $terms[2];
            $ret = LYAPI::apiQuery("/meets/" . $meet_id, "抓取會議 {$meet_id} 資料");
            foreach ($ret->data->議事網資料 ?? [] as $data) {
                foreach ($data->關係文書->議案 ?? [] as $bill) {
                    if (!in_array($law_id, $bill->法律編號)) {
                        continue;
                    } 
                    $billNos[] = $bill->議案編號;
                }
            }
        } elseif ('bill' == $type) {
            $billNo = $terms[1];
            $ret = LYAPI::apiQuery("/bills/" . $billNo, "抓取議案 {$billNo} 資料");
            $billNos[] = $billNo;
            foreach ($ret->data->關連議案 ?? [] as $bill) {
                $billNos[] = $bill->議案編號;
            }
        } elseif ('version' == $type) {
            $law_id = $terms[1];
            $date = $terms[2];
            $ret = LYAPI::apiQuery("/laws/{$law_id}/versions", "抓取法律 {$law_id} 版本");
            $hit_version = null;
            foreach ($ret->lawversions as $lawversion) {
                if ($lawversion->日期 == $date) {
                    $hit_version = $lawversion;
                    break;
                }
            }
            if (is_null($hit_version) or !($hit_version->歷程 ?? false)) {
                if (is_null($hit_version)) {
                    $hit_version = new StdClass;
                }
                $ret = LYAPI::apiQuery("/laws/{$law_id}/progress", "抓取法律 {$law_id} 未議決進度");
                foreach ($ret->歷程 as $log) {
                    if (strpos($log->id, "三讀-") !== 0) {
                        continue;
                    }
                    $log_date = substr($log->id, strlen("三讀-"));
                    // TODO: 有多筆要挑日期最接近的
                    $hit_version->歷程 = $log->bill_log;
                    break;
                }
            }
            foreach ($hit_version->歷程 as $record) {
                if (is_array($record->關係文書)) {
                    foreach ($record->關係文書 as $bill) {
                        if ($bill->billNo ?? false) {
                            $billNos[] = $bill->billNo;
                        }
                    }
                } else if ($record->關係文書->billNo ?? false) {
                    $billNos[] = $record->關係文書->billNo;
                }
            }
        }

        return array_values(array_unique($billNos));
    }

    public static function getVersionsFromBillNos($billnos, $law_id = null)
    {
        if (!count($billnos)) {
            throw new Exception("No bill found");
        }
        $params = [];
        $params[] = 'output_fields=提案單位/提案委員';
        $params[] = 'output_fields=提案編號';
        $params[] = 'output_fields=法律編號';
        $params[] = 'output_fields=提案人';
        $params[] = 'output_fields=對照表';
        $params[] = 'output_fields=議案流程';
        foreach ($billnos as $billno) {
            $params[] = '議案編號=' . $billno;
        }
        $url = '/bills?' . implode('&', $params);
        $bill_data = LYAPI::apiQuery($url, "抓取議案資料");
        $ret = [];
        $ret['現行版本'] = (object)[
            'id' => '現行版本',
            'title' => '現行版本',
            'subtitle' => '',
            'law_id' => $law_id,
            '原始資料' => 'https://www.ly.gov.tw/Pages/ashx/LawRedirect.ashx?CODE=' . $law_id,
            '議案編號' => '',
            '對照表' => [], 
        ];
        // 如果沒指定 law_id ，則檢查最有可能的 law_id
        if (is_null($law_id)) {
            $law_id_count = [];
            foreach ($bill_data->bills as $bill) {
                foreach ($bill->法律編號 ?? [] as $law_id) {
                    if (!array_key_exists($law_id, $law_id_count)) {
                        $law_id_count[$law_id] = 0;
                    }
                    $law_id_count[$law_id] ++;
                }
            }
            arsort($law_id_count);
            $law_id = key($law_id_count);
            $ret['現行版本']->原始資料 = 'https://www.ly.gov.tw/Pages/ashx/LawRedirect.ashx?CODE=' . $law_id;
            $ret['現行版本']->law_id = $law_id;
        }

        $is_3read = false;
        $rule_nos = [];
        $has_origin = false;
        $checked_billWord = [];
        foreach ($bill_data->bills as $bill) {
            if ($bill->提案編號 ?? false) {
                // 檢查提案編號重覆，處理像 201110067380000, 201110067380002, 201110067380003 這種情況
                if (array_key_exists($bill->提案編號, $checked_billWord)) {
                    continue;
                }
                $checked_billWord[$bill->提案編號] = true;
            }
            $version_id = $bill->議案編號;
            $version_data = (object)[
                'id' => $version_id,
                'title' => '',
                'subtitle' => '',
                '議案編號' => $bill->議案編號,
                '原始資料' => "https://ppg.ly.gov.tw/ppg/bills/{$bill->議案編號}/details",
                '提案編號' => $bill->提案編號 ?? null,
                '提案人' => $bill->提案人 ?? [],
                '提案單位' => $bill->{'提案單位/提案委員'} ?? '',
                '對照表' => [],
            ];

            if (!($bill->對照表 ?? false)) {
                continue;
            }

            $bill->對照表 = array_values(array_filter($bill->對照表, function($table){
                if ($table->立法種類 == '修正名稱') {
                    return false;
                }
                return true;
            }));

            if (count($bill->對照表) == 1) {
                $table = $bill->對照表[0];
                if (($table->law_id ?? false) and $table->law_id != $law_id) {
                    continue;
                }
            } else {
                foreach ($bill->對照表 as $table) {
                    if ($table->law_id == $law_id) {
                        break;
                    }
                }
            }
            foreach ($table->rows as $row) {
                $law_content_id = $row->law_content_id ?? null;
                if (($row->現行 ?? false) and property_exists($row, '修正')) {
                    $rule_no = explode('　', $row->現行)[0];
                    $origin = explode('　', $row->現行, 2)[1];
                    $law_content_id = $row->law_content_id ?? null;
                    $new = explode('　', $row->修正)[1];
                } else if (($row->現行法 ?? false) and property_exists($row, '修正')) {
                    $rule_no = explode('　', $row->現行法)[0];
                    $origin = explode('　', $row->現行法, 2)[1];
                    $new = explode('　', $row->修正)[1];
                } elseif ($row->增訂 ?? false) {
                    $rule_no = explode('　', $row->增訂)[0];
                    $origin = '';
                    $new = explode('　', $row->增訂, 2)[1];
                } elseif (property_exists($row, '現行') and $row->現行 == '' and property_exists($row, '修正')) {
                    $rule_no = explode('　', $row->修正)[0];
                    $origin = '';
                    $new = explode('　', $row->修正, 2)[1];
                } elseif (property_exists($row, '增訂') and $row->增訂 == '' and property_exists($row, '審查會通過條文:備註')) {
                    if (strpos(trim($row->條號), '名稱：') === 0) {
                        $rule_no = '法律名稱';
                    } else {
                        $rule_no = explode('　', $row->條號)[0];
                    }
                    $origin = '';
                    $new = '';
                } else {
                    continue;
                }

                if ('' == $row->{'修正'} and $row->{'審查會通過條文:備註'} ?? false) {
                    $new = "(" . $row->{'審查會通過條文:備註'} . ")\n" . $new;
                } elseif ('' == $row->{'增訂'} and $row->{'審查會通過條文:備註'} ?? false) {
                    $new = "(" . $row->{'審查會通過條文:備註'} . ")\n" . $new;
                };
                if (strpos($rule_no, '名稱：') === 0) {
                    $new = explode('：', $rule_no, 2)[1];
                    $rule_no = '法律名稱';
                }
                $ret['現行版本']->{'對照表'}[$rule_no] = [
                    '條文' => $rule_no,
                    '內容' => $origin,
                    'law_content_id' => $law_content_id,
                    '說明' => '',
                ];
                if ($origin) {
                    $has_origin = true;
                }
                $version_data->對照表[$rule_no] = [
                    '條文' => $rule_no,
                    '內容' => $new,
                    '說明' => $row->說明 ?? '',
                ];
            }

            if (is_array($bill->提案人 ?? null)) {
                $version_data->title = sprintf("%s等%d人", $bill->提案人[0], count($bill->提案人));
                $date = strtotime($bill->議案流程[0]->日期[0]);
                $version_data->date = date('Y-m-d', $date);
                $version_data->subtitle = sprintf("%03d/%02d/%02d 提案版本",
                    date('Y', $date) - 1911,
                    date('n', $date),
                    date('j', $date)
                );
            } elseif (strpos($bill->{'提案單位/提案委員'}, '本院') === 0 or
                preg_match('#委員會$#', $bill->{'提案單位/提案委員'})) {
                $version_data->title = '審查報告';
                $date = strtotime($bill->議案流程[0]->日期[0]);
                $committee = str_replace("本院", "", $bill->{'提案單位/提案委員'});
                $version_data->date = date('Y-m-d', $date);
                $version_data->subtitle = sprintf("%03d/%02d/%02d %s",
                    date('Y', $date) - 1911,
                    date('n', $date),
                    date('j', $date),
                    $committee
                );
            } else {
                $version_data->title = $bill->{'提案單位/提案委員'};
                $date = strtotime($bill->議案流程[0]->日期[0]);
                $version_data->date = date('Y-m-d', $date);
                $version_data->subtitle = sprintf("%03d/%02d/%02d",
                    date('Y', $date) - 1911,
                    date('n', $date),
                    date('j', $date),
                );
            }
            $version_data->對照表 = array_values($version_data->對照表);
            $ret[$version_id] = $version_data;

            foreach ($bill->議案流程 as $flow) {
                if (strpos($flow->狀態, '三讀') !== false) {
                    $is_3read = $flow->日期;
                    break;
                }
            }
        }
        $ret['現行版本']->對照表 = array_values($ret['現行版本']->對照表);
        if (!$has_origin) {
            unset($ret['現行版本']);
        }

        // 如果歷程中有三讀的，把三讀內容也抓出來
        if ($is_3read) {
            $versions = LYAPI::apiQuery("/laws/{$law_id}/versions", "抓取法律 {$law_id} 版本");
            $hit_version = null;
            foreach ($versions->lawversions as $version) {
                if (in_array($version->日期, $is_3read)) {
                    $hit_version = $version->版本編號;
                    break;
                }
            }

            if (!is_null($hit_version)) {
                $contents = LYAPI::apiQuery("/law_versions/{$hit_version}/contents?版本追蹤=new", "抓取三讀版本條文 版本：{$hit_version}");
                $version_data = (object)[
                    'id' => '三讀版本',
                    'title' => '三讀版本',
                    'subtitle' => sprintf("%03d/%02d/%02d 三讀版本",
                        date('Y', strtotime($is_3read[0])) - 1911,
                        date('n', strtotime($is_3read[0])),
                        date('j', strtotime($is_3read[0]))
                    ),
                    '議案編號' => '',
                    '原始資料' => "https://www.ly.gov.tw/Pages/ashx/LawRedirect.ashx?CODE={$law_id}",
                    '對照表' => [],
                ];
                foreach ($contents->lawcontents as $lawcontent) {
                    if ($lawcontent->內容 ?? false) {
                        $lawcontent->內容 = str_replace('　　', "\n", $lawcontent->內容);
                    }
                    if ($lawcontent->章名 ?? false) {
                        list($rule_no, $name) = explode(' ', $lawcontent->章名, 2);
                        $version_data->對照表[$rule_no] = [
                            '條文' => $rule_no,
                            '內容' => $name,
                            '說明' => '',
                        ];
                    } else {
                        $rule_no = $lawcontent->條號;
                        $version_data->對照表[$rule_no] = [
                            '條文' => $rule_no,
                            '內容' => $lawcontent->內容,
                            '說明' => '',
                        ];
                    }
                }
                $version_data->對照表 = array_values($version_data->對照表);
                $ret['三讀版本'] = $version_data;
            }
        }

        // 排序 現行 > 三讀 > 審查 > 行政 > 提案日期
        usort($ret, function($a, $b){
            // 現行版本排最前
            if ($a->id == '現行版本') {
                return -1;
            }
            if ($b->id == '現行版本') {
                return 1;
            }

            // 三讀版本排第二
            if (strpos($a->title, '三讀') === 0) {
                return -1;
            }
            if (strpos($b->title, '三讀') === 0) {
                return 1;
            }

            // 審查報告排第三
            if ($a->title == '審查報告') {
                return -1;
            }
            if ($b->title == '審查報告') {
                return 1;
            }

            // 院版排第四
            if (preg_match('#院$#', $a->title) and !preg_match('#院$#', $b->title)) {
                return -1;
            }
            if (!preg_match('#院$#', $a->title) and preg_match('#院$#', $b->title)) {
                return 1;
            }

            // 最後依照提案日期排序
            return strtotime($a->date) - strtotime($b->date);
        });
        return array_values($ret);
    }

    public static function mergeVersionsToTable($versions, $choosed_versions)
    {
        $ret = new StdClass;
        $ret->versions = new StdClass;
        $ret->choosed_version_ids = [];
        $rule_diffs = [];
        $rule_orders = [];
        $first_version = null;
        foreach ($versions as $version) {
            $rule_order = [];
            $ret->versions->{$version->id} = (object)[
                'id' => $version->id,
                'title' => $version->title,
                'subtitle' => $version->subtitle,
                '議案編號' => $version->議案編號,
                '原始資料' => $version->原始資料,
                'showed' => true,
                'first_version' => false,
            ];
            if ('現行版本' == $version->id) {
                // 現行版本一定要顯示，並在第一個
            } else if (count($choosed_versions) and !in_array($version->id, $choosed_versions)) {
                $ret->versions->{$version->id}->showed = false;
                continue;
            }
            if (is_null($first_version)) {
                $ret->versions->{$version->id}->first_version = true;
                $first_version = $version->id;
            }
            $ret->choosed_version_ids[] = $version->id;

            foreach ($version->對照表 as $row) {
                $rule_no = $row['條文'];
                $rule_order[] = $rule_no;

                if (!array_key_exists($rule_no, $rule_diffs)) {
                    $rule_diffs[$rule_no] = (object)[
                        '條文' => $rule_no,
                        'versions' => new StdClass,
                    ];
                }
                $rule_diffs[$rule_no]->versions->{$version->id} = (object)[
                    '條文' => $rule_no,
                    '內容' => $row['內容'],
                    '說明' => $row['說明'],
                ];
            }
            $rule_orders[$version->id] = $rule_order;
        }
        self::$_rule_orders = $rule_orders;
        try {
            uksort($rule_diffs, ['DiffHelper', 'sortRuleNo']);
        } catch (Exception $e) {
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'rule_diffs' => self::$_rule_orders,
            ]);
            exit;
        }
        $ret->rule_diffs = $rule_diffs;
        $ret->rule_diffs = array_values($ret->rule_diffs);

        return $ret;
    }

    public static function ruleNoToNumber($rule_no)
    {
        if (preg_match('#^第(.*)條$#u', $rule_no, $matches)) {
            return OpenFun\LyTcToolkit\LyTcToolkit::parseNumber($matches[1]) * 1000;
        }

        if (preg_match('#^第(.*)條之([^第]*)(第.*項)?$#u', $rule_no, $matches)) {
            return OpenFun\LyTcToolkit\LyTcToolkit::parseNumber($matches[1]) * 1000 + OpenFun\LyTcToolkit\LyTcToolkit::parseNumber($matches[2]);
        }

        return 0;
        throw new Exception("Invalid rule_no: $rule_no");
    }

    public static $_rule_orders = [];

    public static function sortRuleNo($a, $b)
    {
        $m = 0;
        foreach (self::$_rule_orders as $key => $rule_order) {
            // 現行版本不參與排序，因為他不會照順序排
            if ($key == '現行版本') {
                continue;
            }
            $a_index = array_search($a, $rule_order);
            $b_index = array_search($b, $rule_order);
            if ($a_index === false or $b_index === false) {
                continue;
            }
            if ($a_index > $b_index) {
                $m ++;
            } elseif ($a_index < $b_index) {
                $m --;
            }
        }
        if ($m == 0) {
            $m = self::ruleNoToNumber($a) - self::ruleNoToNumber($b);
        }
        return $m;
    }
}
