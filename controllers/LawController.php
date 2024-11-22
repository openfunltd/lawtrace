<?php

class LawController extends MiniEngine_Controller
{
    public function showAction($law_id)
    {
        if (! ctype_digit($law_id)) {
            header('HTTP/1.1 400 Bad Request');
            echo "<h1>400 Bad Request</h1>";
            exit;
        }
        $this->view->law_id = $law_id;
    }
}
