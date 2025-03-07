<?php

class SearchController extends MiniEngine_Controller
{
    public function indexAction()
    {
        $this->view->q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) ?? '';
        // billNo 直接轉到條文比較工具
        if (preg_match('#^\d+$#', $this->view->q) and strlen($this->view->q) > 10) {
            return $this->redirect("/law/compare?source=bill:" . $this->view->q);
        }
    }

    public function plenaryAction()
    {
        $uri_terms = explode('/', $_SERVER['REQUEST_URI']);
        $meet_id = $uri_terms[3] ?? null;
        $data = PlenaryHelper::getData($meet_id);
        $this->view->data = $data;
    }
}
