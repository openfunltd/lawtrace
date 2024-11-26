<?php

class LawarticleController extends MiniEngine_Controller
{
    public function showAction($law_content_id)
    {
        $this->view->law_content_id = $law_content_id;
    }
}
