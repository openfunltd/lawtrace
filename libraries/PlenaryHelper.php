<?php

class PlenaryHelper
{
    public static function getData($meet_id = null)
    {
        if (is_null($meet_id) or trim($meet_id) == '') {
            $ret = LYAPI::apiQuery("/meets?會議種類=院會&limit=1", "取得最新院會");
            $meet_id = $ret->meets[0]->會議代碼;
            $meet_data = $ret->meets[0];
        } else {
            $ret = LYAPI::apiQuery("/meets/{$meet_id}", "取得院會資料");
            $meet_data = $ret->data;
        }

        $params = [];
        $params[] = 'output_fields=黨籍';
        $params[] = 'output_fields=委員姓名';
        $params[] = '屆=' . $meet_data->屆;
        $ret = LYAPI::apiQuery("/legislators?" . implode('&', $params), "取得立委資料");
        $legislators = [];
        foreach ($ret->legislators as $legislator) {
            $legislators[$legislator->委員姓名] = $legislator;
        }

        $bill_list = $meet_data->議事網資料[0]->關係文書->議案;
        $bill_list = array_filter($bill_list, function($bill) {
            if (!($bill->法律編號 ?? false)) {
                return false;
            }
            return true;
        });
        $bill_list = array_values($bill_list);

        $laws = [];
        $bills = [];
        foreach ($bill_list as $bill) {
            $laws[$bill->法律編號[0]] = (object)[
                'data' => null,
                'bills' => [],
            ];
            $bills[$bill->議案編號] = true;
        }

        // 抓取法律資料
        $params = [];
        $params[] = 'output_fields=法律編號';
        $params[] = 'output_fields=名稱';
        $params[] = 'output_fields=其他名稱';
        $params[] = 'output_fields=別名';
        $params[] = 'output_fields=最新版本';
        foreach (array_keys($laws) as $law_id) {
            $params[] = '法律編號=' . $law_id;
        }
        $ret = LYAPI::apiQuery("/laws?" . implode('&', $params), "取得法律資料");
        foreach ($ret->laws as $law_data) {
            $laws[$law_data->法律編號]->data = $law_data;
        }

        // 抓取議案資料
        $params = [];
        $params[] = 'output_fields=議案編號';
        $params[] = 'output_fields=法律編號';
        $params[] = 'output_fields=提案單位/提案委員';
        $params[] = 'output_fields=提案人';
        $params[] = 'output_fields=對照表';
        $params[] = 'output_fields=議案名稱';
        foreach (array_keys($bills) as $bill_id) {
            $params[] = '議案編號=' . $bill_id;
        }
        $ret = LYAPI::apiQuery("/bills?" . implode('&', $params), "取得議案資料");
        foreach ($ret->bills as $bill_data) {
            if ($bill_data->提案人 ?? false) {
                $party = $legislators[$bill_data->提案人[0]]->黨籍 ?? null;
                $bill_data->party_img_path = PartyHelper::getImage($party);
            }
            $bill_data->主提案 = LawHistoryHelper::trimProposer($bill_data->{'提案單位/提案委員'} ?? false);
            $bill_data->article_numbers = LawHistoryHelper::getArticleNumbers($bill_data->對照表[0] ?? new StdClass);

            $bills[$bill_data->議案編號] = $bill_data;
            foreach ($bill_data->法律編號 as $law_id) {
                $laws[$law_id]->bills[] = $bill_data->議案編號;
            }
        }

        return (object)[
            'laws'=> $laws,
            'bills'=> $bills,
            'meet' => $meet_data,
        ];
    }
}
