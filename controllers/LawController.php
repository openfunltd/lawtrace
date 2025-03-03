<?php

class LawController extends MiniEngine_Controller
{
    public function showAction($law_id)
    {
        $version_id_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';

        $this->view->law_id = $law_id;
        $this->view->version_id_input = $version_id_input;
        $this->view->law = $this->getLawData($law_id);
    }

    public function historyAction($law_id)
    {
        $version_id_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';

        $this->view->law_id = $law_id;
        $this->view->version_id_input = $version_id_input;
    }

    public function singleAction($law_content_id)
    {
        $version_id_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';

        $this->view->law_content_id = $law_content_id;
        $this->view->version_id_input = $version_id_input;
    }

    public function diffAction($law_id)
    {
        $version_id_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';

        $this->view->law_id = $law_id;
        $this->view->version_id_input = $version_id_input;
        $this->view->law = $this->getLawData($law_id);
    }

    public function compareAction()
    {
        $source_input = filter_input(INPUT_GET, 'source', FILTER_SANITIZE_SPECIAL_CHARS) ?? Null;

        // 從來源代碼中取得相關的議案編號
        $billNos = DiffHelper::getBillNosFromSource($source_input);
        $type = explode(':', $source_input)[0];
        if ('meet' == $type) {
            $meet_id = explode(':', $source_input)[1];
            $this->view->meet = LYAPI::apiQuery("/meets/{$meet_id}", "抓取會議 {$meet_id} 資料")->data;
        } elseif ('bill' == $type) {
            $bill_id = explode(':', $source_input)[1];
            $this->view->bill = LYAPI::apiQuery("/bills/{$bill_id}", "抓取議案 {$bill_id} 資料")->data;
        } elseif ('version' == $type) {
            $law_id = explode(':', $source_input)[1];
            $this->view->law = LYAPI::apiQuery("/laws/{$law_id}", "抓取法律 {$law_id} 資料")->data;
        }
        $this->view->source_type = $type;
        $this->view->source_input = $source_input;

        // 透過議案編號取得版本資訊
        $all_versions = DiffHelper::getVersionsFromBillNos($billNos);
        $this->view->law_id = $law_id = $all_versions[0]->law_id;
        $this->view->version_id_input = 'latest';
        // 如果有透過 $_GET['version'] 指定要篩選的版本，就只取出這些版本的對照表
        if ($_GET['version'] ?? false) {
            $versions = array_values(array_filter($all_versions, function ($version) {
                return in_array($version->id, $_GET['version']);
            }));
        } else {
            $versions = $all_versions;
        }

        // 列出哪些版本有被選取（用在 checkbox 勾選上）
        $choosed_version_ids = array_map(function ($version) {
            return $version->id;
        }, $versions);
        $this->view->choosed_version_ids = $choosed_version_ids;

        // 整合出對照表需要的資料
        $this->view->diff = DiffHelper::mergeVersionsToTable($versions);
        $this->view->law = LYAPI::apiQuery("/laws/{$law_id}", "抓取法律 {$law_id} 資料")->data;
    }

    public function getLawData($law_id)
    {
        if (!ctype_digit($law_id)) {
            header('HTTP/1.1 400 Bad Request');
            echo "<h1>400 Bad Request</h1>";
            echo "<p>Invalid law_id</p>";
            exit;
        }

        $res = LYAPI::apiQuery("/law/{$law_id}" ,"查詢法律編號：{$law_id} ");
        $res_error = $res->error ?? true;
        if ($res_error) {
            header('HTTP/1.1 404 No Found');
            echo "<h1>404 No Found</h1>";
            echo "<p>No law data with law_id {$law_id}</p>";
            exit;
        }
        return $res->data;
    }
}
