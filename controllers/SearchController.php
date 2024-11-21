<?php

class SearchController extends MiniEngine_Controller
{
    public function indexAction()
    {
        $this->view->q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) ?? '';
    }
}
