<?php

class LawController extends MiniEngine_Controller
{
    public function showAction($law_id)
    {
        if (! ctype_digit($law_id)) {
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

        $law = $res->data;
        $version_input = filter_input(INPUT_GET, 'version',FILTER_SANITIZE_STRING) ?? 'latest';
        $res = LYAPI::apiQuery("/law/{$law_id}/versions", "查詢 {$law->名稱} 各法律版本");
        $version_cnt = $res->total ?? 0;
        if ($version_cnt == 0) {
            header('HTTP/1.1 404 No Found');
            echo "<h1>404 No Found</h1>";
            echo "<p>No version data with law_id {$law_id}</p>";
            exit;
        }

        $versions = $res->lawversions;
        if ($version_input != 'latest') {
            $invalid_version = true;
            foreach ($versions as $version) {
                $version_id = $version->版本編號 ?? NULL;
                if ($version_input == $version_id) {
                    $invalid_version = false;
                    $version_id_selected = $version_id;
                    $version_selected = $version;
                    break;
                }
            }
            if ($invalid_version) {
                header('HTTP/1.1 404 No Found');
                echo "<h1>404 No Found</h1>";
                echo "<p>No version data with version_id {$version_input}</p>";
                exit;
            }
        }

        //versions order by date DESC
        usort($versions, function($v1, $v2) {
            $date_v1 = $v1->日期 ?? '';
            $date_v2 = $v2->日期 ?? '';
            return $date_v2 <=> $date_v1;
        });
        if ($version_input == 'latest') {
            foreach ($versions as $version) {
                $version_id = $version->版本編號 ?? NULL;
                if (isset($version_id)) {
                    $version_id_selected = $version_id;
                    $version_selected = $version;
                    break;
                }
            }
        }

        $this->view->law_id = $law_id;
        $this->view->version_id_selected = $version_id_selected;
        $this->view->law = $law;
        $this->view->versions = $versions;
        $this->view->version_selected = $version_selected;
    }
}
