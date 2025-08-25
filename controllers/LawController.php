<?php

class LawController extends MiniEngine_Controller
{
    public function showAction($law_id)
    {
        $version_id_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';

        $this->view->law_id = $law_id;
        $this->view->version_id_input = $version_id_input;
        $this->view->law = $this->getLawData($law_id);

        $versions_data = LawVersionHelper::getVersionsData($law_id, $version_id_input);
        $versions = $versions_data->versions ?? null;
        $version_selected = $versions_data->version_selected ?? null;
        $version_id_selected = $versions_data->version_id_selected ?? null;

        $this->view->versions_data = $versions_data;

        $is_announced = true;
        if (is_null($version_id_selected)) {
            //在立法院法律系統中查無此版本時，檢查是係否因總統府尚未公告的關係
            $res = LYAPI::apiQuery("/laws?limit=100", "近期三讀法律");
            $laws_new = $res->laws ?? [];
            foreach ($laws_new as $law_new) {
                $law_id_new = $law_new->法律編號;
                $date_new = $law_new->最新版本->日期;
                $version_id_new = "$law_id_new:$date_new";
                if ($law_id_new == $law_id and $version_id_new == $version_id_input) {
                    $is_announced = false;
                    break;
                }
            }
        }
        $this->view->is_announced = $is_announced;

        $contents = [];
        $source = '';

        if (isset($version_selected)) {
            $this->view->version = $version_selected;
            $res = LYAPI::apiQuery(
                    "/law_contents?版本編號={$version_id_selected}&limit=1000",
                        "{查詢法律版本為 {$version_id_selected} 的法律條文 }"
            );
            $contents = $res->lawcontents ?? [];
            $source = "version:{$law_id}:{$version_selected->日期}";
        }

        $this->view->contents = $contents;
        $this->view->source = $source;
    }

    public function historyAction($law_id)
    {
        $version_id_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';
        $source_input = filter_input(INPUT_GET, 'source', FILTER_SANITIZE_SPECIAL_CHARS);

        $this->view->law_id = $law_id;
        $this->view->version_id_input = $version_id_input;
        $this->view->law = self::getLawData($law_id);

        $versions_data = LawVersionHelper::getVersionsWithProgresses($law_id, $version_id_input, $source_input);
        $versions = $versions_data->versions;
        $versions_in_terms = $versions_data->versions_in_terms;
        $version_selected = $versions_data->version_selected;
        $version_id_selected = $versions_data->version_id_selected;
        $term_selected = $versions_data->term_selected;
        $this->view->version = $version_selected;
        $this->view->versions_data = $versions_data;
        $this->view->single_version = false;
        $history_groups = $version_selected->歷程 ?? [];

        if ($source_input) {
            $ret = DiffHelper::getBillNosFromSource($source_input);
            $type = explode(':', $source_input)[0];
            if ('meet' == $type) {
                $meet_id = explode(':', $source_input)[1];
                $meet_related_history_groups = array_values(array_filter($history_groups, function ($group) use ($meet_id) {
                    foreach ($group->bill_log as $log) {
                        if (($log->會議代碼 ?? false) == $meet_id) {
                            return true;
                        }
                    }
                    return false;
                }));
                //會議還沒開的時候，還不會有關聯議案，會使得 $history_groups 整個是空的，改成陳列所有的經歷過程
                if (!empty($meet_related_history_groups)) {
                    $history_groups = $meet_related_history_groups;
                }
                $this->view->meet = $ret->meet;
            } elseif ('bill' == $type) {
                $bill_id = explode(':', $source_input)[1];
                $history_groups = array_values(array_filter($history_groups, function ($group) use ($bill_id) {
                    foreach ($group->bill_log as $log) {
                        $related_doc = $log->關係文書;
                        if (is_array($related_doc) and count($related_doc) > 0) {
                            $related_doc = $log->關係文書[0];
                        }
                        if (($related_doc->billNo ?? false) == $bill_id) {
                            return true;
                        }
                    }
                    return false;
                }));
                if ($history_groups[0]->id == '未分類') { // 未分類就只要留一個就好
                    $history_groups[0]->bill_log = array_values(array_filter($history_groups[0]->bill_log, function ($log) use ($bill_id) {
                        return ($log->關係文書->billNo ?? false) == $bill_id;
                    }));
                    $history_groups[0]->id = '單一版本';
                }
                $this->view->single_version = true;
                $this->view->bill = $ret->bill;
            } elseif ('version' == $type) {
                $law_id = explode(':', $source_input)[1];
                $this->view->single_version = true;
                $this->view->law = LYAPI::apiQuery("/laws/{$law_id}", "抓取法律 {$law_id} 資料")->data;
            }
            $this->view->source_type = $type;
            $this->view->source = $source_input;
        }
        $history_groups = LawHistoryHelper::updateDetails($history_groups, $term_selected);
        $this->view->history_groups = $history_groups;
    }

    public function singleAction($law_content_id)
    {
        $version_id_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';

        $this->view->law_content_id = $law_content_id;
        $this->view->version_id_input = $version_id_input;
        $id_array = explode(':', $law_content_id);
        $law_id = $id_array[0];
        $this->view->law = $this->getLawData($law_id);
        $this->view->law_id = $law_id;

        $res = LYAPI::apiQuery("/law_content/{$law_content_id}" ,"查詢法律條文：{$law_content_id} ");
        $law_content = $res->data ?? new stdClass();
        $chapter_name = $law_content->章名 ?? '';
        $is_chapter = ($chapter_name != '');
        if (empty($law_content) or $is_chapter) {
            header('HTTP/1.1 404 No Found');
            echo "<h1>404 No Found</h1>";
            echo "<p>No law_content data with law_content_id {$law_content_id}</p>";
            exit;
        }
        $this->view->law_content = $law_content;
        $this->view->chapter_name = $chapter_name;

        $law_content_name = $law_content->條號;
        $versions_data = LawVersionHelper::getVersionsForSingle($law_id, $version_id_input, $law_content_name);
        if (is_null($versions_data)) {
            header('HTTP/1.1 404 No Found');
            echo "<h1>404 No Found</h1>";
            echo "<p>No versions data with law_id {$law_id}</p>";
            exit;
        }
        $this->view->version_data = $versions_data;

        $version_selected = $versions_data->version_selected ?? null;
        if (is_null($version_selected)) {
            header('HTTP/1.1 404 No Found');
            echo "<h1>404 No Found</h1>";
            echo "<p>No version data with version_id {$version_id_input}</p>";
            exit;
        }
        $res = LYAPI::apiQuery(
            "/law_contents?版本編號={$versions_data->version_id_selected}&limit=1000",
            "{查詢法律版本為 {$versions_data->version_id_selected} 的法律條文 }"
        );
        $this->view->contents = $res->lawcontents ?? [];
        //TODO 當 API 回傳空的 lawcontents 時要在頁面上呈現/說明
    }

    public function diffAction($law_id)
    {
        $version_id_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';

        $this->view->law_id = $law_id;
        $this->view->version_id_input = $version_id_input;
        $this->view->law = $this->getLawData($law_id);

        $versions_data = LawVersionHelper::getVersionsData($law_id, $version_id_input);
        $versions_in_terms_filtered = $versions_data->versions_in_terms_filtered;
        $version_selected = $versions_data->version_selected;
        $version_previous = $versions_data->version_previous;
        $version_id_selected = $versions_data->version_id_selected;
        $version_id_previous = $versions_data->version_id_previous;
        $this->view->version = $version_selected;
        $this->view->versions_data = $versions_data;
        $source = '';

        if (isset($version_selected)) {
            $res = LYAPI::apiQuery(
                "/law_version/{$version_id_selected}/contents",
                "查詢版本條文 版本：{$version_id_selected}"
            );
            $res_total = $res->total ?? 0;
            if ($res_total == 0) {
                header('HTTP/1.1 404 No Found');
                echo "<h1>404 No Found</h1>";
                echo "<p>No law_conetnts with law_version_id {$version_id_selected}</p>";
                exit;
            }
            $source = "version:{$law_id}:{$version_selected->日期}";
            $this->view->law_contents = $res->lawcontents;
        }

        if (!is_null($version_id_previous)) {
            $res = LYAPI::apiQuery(
                "/law_version/{$version_id_previous}/contents",
                "查詢上一個版本條文 版本：{$version_id_previous}"
            );
            if ($res_total == 0) {
                header('HTTP/1.1 404 No Found');
                echo "<h1>404 No Found</h1>";
                echo "<p>No law_conetnts with previous law_version_id {$version_id_previous}</p>";
                exit;
            }
            $this->view->law_contents_previous = $res->lawcontents;
        }
        $this->view->source = $source;
    }

    public function compareAction()
    {
        $source_input = filter_input(INPUT_GET, 'source', FILTER_SANITIZE_SPECIAL_CHARS) ?? Null;

        // 從來源代碼中取得相關的議案編號
        $ret = DiffHelper::getBillNosFromSource($source_input);
        $type = explode(':', $source_input)[0];
        if ('meet' == $type) {
            $meet_id = explode(':', $source_input)[1];
            $this->view->meet = $ret->meet;
        } elseif ('bill' == $type) {
            $bill_id = explode(':', $source_input)[1];
            $this->view->bill = $ret->bill;
        } elseif ('version' == $type) {
            $law_id = explode(':', $source_input)[1];
        }
        if ($ret->version_id_input ?? false) {
            $this->view->version_id_input = $ret->version_id_input;
        }
        $this->view->source_type = $type;
        $this->view->source = $source_input;

        // 透過議案編號取得版本資訊
        $all_versions = DiffHelper::getVersionsFromBillNos($ret->billNos, $source_input);
        if ('bill' == $type) {
            $this->view->version_id_input = $all_versions->version_id_input;
        }
        $this->view->law_id = $law_id = $all_versions->law_id;
        // 如果有透過 $_GET['version'] 指定要篩選的版本，就只取出這些版本的對照表
        if ($_GET['version'] ?? false) {
            $versions = array_values(array_filter($all_versions->versions, function ($version) {
                return in_array($version->id, $_GET['version']);
            }));
        } else {
            $versions = $all_versions->versions;
        }

        // 列出哪些版本有被選取（用在 checkbox 勾選上）
        $choosed_version_ids = array_map(function ($version) {
            return $version->id;
        }, $versions);
        $this->view->choosed_version_ids = $choosed_version_ids;

        // 整合出對照表需要的資料
        $this->view->diff = DiffHelper::mergeVersionsToTable($all_versions->versions, $_GET['version'] ?? []);
        $this->view->choosed_version_ids = $this->view->diff->choosed_version_ids;
        $this->view->law = LYAPI::apiQuery("/laws/{$law_id}", "抓取法律 {$law_id} 資料")->data;
    }

    public function sub_lawsAction($law_id)
    {
        $this->view->law_id = $law_id;
        $this->view->law = self::getLawData($law_id);

        //查詢子法
        $res = LYAPI::apiQuery(
            "/laws?母法編號={$law_id}&類別=子法&limit=100",
            "查詢子法列表, 母法: {$law_id}"
        );

        $law_moj_base = 'https://law.moj.gov.tw/Law/LawSearchResult.aspx?ty=ONEBAR&kw=';
        $sub_laws = [];
        $sub_law_count = $res->total ?? 0;
        $sub_laws = $res->laws ?? [];
        foreach ($sub_laws as $sub_law) {
            $aliases = $sub_law->別名 ?? [];
            $other_names = $sub_law->其他名稱 ?? [];
            $sub_law->aliases_merged = array_merge($aliases, $other_names);
            $sub_law->law_moj_url = $law_moj_base . $sub_law->名稱;
        }

        $this->view->sub_law_count = $sub_law_count;
        $this->view->sub_laws = $sub_laws;
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
