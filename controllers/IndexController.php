<?php

class IndexController extends MiniEngine_Controller
{
    public function indexAction()
    {
        $this->view->app_name = getenv('APP_NAME');
        $this->view->third_read_laws = IndexHelper::getThirdReadList();
        $this->view->exammed_laws = IndexHelper::getExammedLaws();
        $this->view->examming_laws = IndexHelper::getExammingLaws();
    }

    public function robotsAction()
    {
        header('Content-Type: text/plain');
        echo "#\n";
        return $this->noview();
    }
}
