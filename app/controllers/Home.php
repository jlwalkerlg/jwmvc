<?php

/**
 * Home controller.
 */
class Home extends Controller
{
    /**
     * Display home page.
     **/
    public function index()
    {
        $data['title'] = 'Welcome';

        $this->render('includes/header', $data);
        $this->render('includes/nav', $data);
        $this->render('pages/index', $data);
        $this->render('includes/footer', $data);
    }
}
