<?php

/**
 * Users controller.
 */
class Users extends Controller
{
    /**
     * Show register page.
     */
    public function new()
    {
        $user = new User;
        $user->email = '';

        $data['user'] = $user;
        $data['errors'] = [];

        $this->render('includes/header', $data);
        $this->render('includes/nav', $data);
        $this->render('users/register', $data);
        $this->render('includes/footer', $data);
    }

    /**
     * Create new user in database.
     */
    public function create()
    {
        $user = new User;
        $user->email = $_POST['email'] ?? '';

        // Validate CSRF token.
        if (CSRF::validateToken()) {

            // Validate form inputs for new user.
            $this->validate($_POST, [
                'email' => 'required|format:email|max:255',
                'password' => 'required|min:10|max:255',
                'confirm_password' => 'matches:password'
            ]);

            // If validations passed, hash password and save new user in database.
            if ($this->validated) {
                $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                if ($user->save()) {
                    // Log user in.
                    Session::login($user);
                    // Flash success message on redirect.
                    Session::flash('success', 'Welcome!');
                    redirect('/');
                }
            }
        }

        // Validations failed, CSRF token invalid, or problem with
        // database while creating new user -- render register form again.
        $data['user'] = $user;
        $data['errors'] = $this->validationErrors ?? [];

        $this->render('includes/header', $data);
        $this->render('includes/nav', $data);
        $this->render('users/register', $data);
        $this->render('includes/footer', $data);
    }

    /**
     * Show login form.
     */
    public function showLogin()
    {
        $user = new User;
        $user->email = '';

        $data['user'] = $user;

        $this->render('includes/header', $data);
        $this->render('includes/nav', $data);
        $this->render('users/login', $data);
        $this->render('includes/footer', $data);
    }

    /**
     * Log user in.
     */
    public function login()
    {
        $user = new User;
        $user->email = $_POST['email'] ?? '';

        if (CSRF::validateToken()) {
            $existingUser = DB::table('users')->where('email', $user->email)->first();
            if (password_verify($_POST['password'], $existingUser->password)) {
                Session::login($existingUser);
                Session::flash('success', 'Logged in.');
                redirect('/');
            } else {
                Session::flash('error', 'Failed to login.');
            }
        }

        $data['user'] = $existingUser ?? $user;

        $this->render('includes/header', $data);
        $this->render('includes/nav', $data);
        $this->render('users/login', $data);
        $this->render('includes/footer', $data);
    }

    /**
     * Log user out.
     */
    public function logout()
    {
        Session::logout();
        Session::flash('success', 'Logged out.');
        redirect('/');
    }
}
