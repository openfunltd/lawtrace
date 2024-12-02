<?php

class LawdiffController extends MiniEngine_Controller
{
    public function showAction($bill_no)
    {
        $this->view->bill_no = $bill_no;
    }
}
