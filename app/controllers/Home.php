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

        render('includes/header', $data);
        render('includes/nav', $data);
        render('pages/index', $data);
        render('includes/footer', $data);
    }
}
