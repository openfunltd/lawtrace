<?php

class LawController extends MiniEngine_Controller
{
    public function showAction($law_id)
    {
        $version_id_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';

        $this->view->law_id = $law_id;
        $this->view->version_id_input = $version_id_input;
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
    }

    public function compareAction()
    {
        $source_input = filter_input(INPUT_GET, 'source', FILTER_SANITIZE_SPECIAL_CHARS) ?? Null;

        // 從來源代碼中取得相關的議案編號
        $billNos = DiffHelper::getBillNosFromSource($source_input);

        // 透過議案編號取得版本資訊
        $all_versions = DiffHelper::getVersionsFromBillNos($billNos);
        $this->view->law_id = $law_id = $all_versions[0]->law_id;
        $this->view->version_id_input = 'latest';
        // 如果有透過 $_GET['version'] 指定要篩選的版本，就只取出這些版本的對照表
        if ($_GET['version'] ?? false) {
            $versions = array_filter($all_versions, function ($version) {
                return in_array($version->id, $_GET['version']);
            });
        } else {
            $versions = $all_versions;
        }

        // 列出哪些版本有被選取（用在 checkbox 勾選上）
        $choosed_version_ids = array_map(function ($version) {
            return $version->id;
        }, $versions);

        // 整合出對照表需要的資料
        $diff = DiffHelper::mergeVersionsToTable($versions);

        $this->view->law = LYAPI::apiQuery("/laws/{$law_id}", "抓取法律 {$law_id} 資料")->data;
    }
}
