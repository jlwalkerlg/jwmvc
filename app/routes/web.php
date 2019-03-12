<?php

// HOME
// ==================================================
Router::get('/', 'Home@index');


// USERS
// ==================================================
Router::get('/register', 'Users@new');

Router::post('/register', 'Users@create');

Router::get('/login', 'Users@showLogin');

Router::post('/login', 'Users@login');

Router::get('/logout', 'Users@logout');
