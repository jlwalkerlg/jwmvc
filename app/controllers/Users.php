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
        $user = Session::getAndUnset('formValues') ?? [];
        $user['email'] = $user['email'] ?? '';

        $errors = Session::getAndUnset('formErrors') ?? [];

        $data['user'] = $user;
        $data['errors'] = $errors;

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
        // Validate form inputs for new user.
        $this->validate($_POST, [
            'email' => 'required|format:email|max:255',
            'password' => 'required|min:10|max:255',
            'confirm_password' => 'matches:password'
        ]);

        // Validate CSRF token.
        if (CSRF::validateToken()) {
            // Instantiate new user object.
            $user = new User;
            $user->email = $_POST['email'];
            // Hash password.
            $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            // Save new user in database.
            if ($user->save()) {
                // Log user in.
                Session::login($user);
                // Flash success message on redirect.
                Session::flash('success', 'Welcome!');
                redirect('posts');
            }
        }

        Session::flash('error', 'Failed to create new user.');
        redirect(Session::get('back'));
    }

    /**
     * Show login form.
     */
    public function showLogin()
    {
        $user = Session::getAndUnset('formValues') ?? [];
        $user['email'] = $user['email'] ?? '';

        $errors = Session::getAndUnset('formErrors') ?? [];

        $data['user'] = $user;
        $data['errors'] = $errors;

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
        if (CSRF::validateToken()) {
            $user = new User;
            $user->email = $_POST['email'] ?? '';

            $existingUser = DB::table('users')->where('email', $user->email)->first();

            if (password_verify($_POST['password'], $existingUser->password)) {
                Session::login($existingUser);
                Session::flash('success', 'Logged in.');
                redirect('/');
            }
        }

        Session::set('formValues', $_POST);
        Session::flash('error', 'Failed to login.');
        redirect(Session::get('back'));
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
