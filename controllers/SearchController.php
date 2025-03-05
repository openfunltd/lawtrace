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
}
