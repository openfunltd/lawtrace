<?php

class IndexHelper
{
    public static function getExammedLaws()
    {
        $path = '/bills/?提案來源=審查報告&議案類別=法律案&sort=提案日期&output_fields=法律編號&output_fields=議案名稱&output_fields=提案日期&output_fields=議案狀態&output_fields=提案單位/提案委員&output_fields=議案編號';
        $res = LYAPI::apiQuery($path, '近期出爐的審查報告');

        $laws = [];
        foreach ($res->bills as $bill) {
            if (!($bill->{'法律編號:str'}[0] ?? false)) {
                continue;
            }
            $laws[] = $bill;
        }
        return $laws;
    }

    public static function getExammingLaws()
    {
        $path = '/meets?會議種類=委員會&會議種類=聯席會議&limit=100';
        $res = LYAPI::apiQuery($path, '近期審查會議');

        $meet_laws = [];
        $bills = [];
        foreach ($res->meets as $meet) {
            if (!($meet->議事網資料 ?? false)) {
                continue;
            }
            $meet_id = $meet->會議代碼;
            $laws = [];
            foreach ($meet->議事網資料 as $data) {
                foreach ($data->關係文書->議案 ?? [] as $bill) {
                    if (!($bill->法律編號 ?? false)) {
                        continue;
                    }
                    $law_id = $bill->法律編號[0];
                    if (!array_key_exists("{$meet_id}-{$law_id}", $meet_laws)) {
                        $meet_laws["{$meet_id}-{$law_id}"] = [
                            'law_id' => $law_id,
                            'law_name' => $bill->{'法律編號:str'}[0],
                            'meet' => $meet,
                            'status' => '審查中',
                            'proposors' => [],
                            'bills' => [],
                        ];
                    }
                    $meet_laws["{$meet_id}-{$law_id}"]['bills'][] = $bill->議案編號;
                    $bills[$bill->議案編號] = null;
                }
            }
        }

        foreach (array_chunk(array_keys($bills), 100) as $chunked_bills) {
            $bill_params = array_map(function ($bill) {
                return "議案編號={$bill}";
            }, $chunked_bills);

            $bill_data = json_decode(file_get_contents('https://v2.ly.govapi.tw/bills?limit=300&' . implode('&', $bill_params)));
            foreach ($bill_data->bills as $bill) {
                $bills[$bill->議案編號] = $bill;
            }
        }
        foreach ($meet_laws as $key => $meet_law) {
            foreach ($meet_law['bills'] as $bill_id) {
                $meet_laws[$key]['bills'][] = $bills[$bill_id];
                if ($bills[$bill_id]->議案狀態 == '審查完畢') {
                    $meet_laws[$key]['status'] = '審查完畢';
                }

                if ($bills[$bill_id]->提案人[0] ?? false) {
                    $meet_laws[$key]['proposors'][] = $bills[$bill_id]->提案人[0];
                } else {
                    $meet_laws[$key]['proposors'][] = $bills[$bill_id]->{'提案單位/提案委員'};
                }
            }
        }
        return $meet_laws;
    }

    public static function getThirdReadList()
    {
        $limit = 100;
        $res = LYAPI::apiQuery('/laws?limit=' . $limit, '近期三讀會議');
        $laws = [];
        $versions = [];

        foreach ($res->laws as $law) {
            $version_id = "{$law->法律編號}:{$law->最新版本->版本編號}";
            $laws[$version_id] = [
                'law' => $law,
            ];
            $versions[] = "版本編號={$version_id}";
        }

        // 再抓看看有沒有版本歷程
        $lawversions = [];
        foreach (array_chunk($versions, 50) as $chunk_versions) {
            $ret = json_decode(file_get_contents('https://v2.ly.govapi.tw/law_versions?' . implode('&', $chunk_versions)));
            $lawversions = array_merge($lawversions, $ret->lawversions);
        }

        foreach ($lawversions as $law_version) {
            $version_id = $law_version->版本編號;
            $laws[$version_id]['version'] = [
                '提案人' => [],
                '有院版' => false,
                '有完整歷程' => false,
                '最早提案日期' => null,
                '審查報告日期' => null,
            ];

            if ($law_version->歷程 ?? false) {
                $laws[$version_id]['version']['有完整歷程'] = true;
                foreach ($law_version->歷程 as $history) {
                    if ($history->進度 == '一讀' and ($history->主提案 ?? false)) {
                        $laws[$version_id]['version']['提案人'][] = $history->主提案;
                        foreach (['行政院', '司法院', '監察院', '考試院'] as $院) {
                            if (strpos($history->主提案, $院) !== false) {
                                $laws[$version_id]['version']['有院版'] = true;
                            }
                        }
                        if (is_null($laws[$version_id]['version']['最早提案日期'])) {
                            $laws[$version_id]['version']['最早提案日期'] = $history->會議日期;
                        }
                    }
                    if ($history->進度 == '委員會審查') {
                        $laws[$version_id]['version']['審查報告日期'] = $history->會議日期;
                    }
                }
            }
        }
        return $laws;
    }
}
