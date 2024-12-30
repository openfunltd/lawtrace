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
}
