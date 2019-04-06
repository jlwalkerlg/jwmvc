<?php

/**
 * Escape variable for HTML output.
 *
 * @param mixed $var Variable to be escaped.
 * @return mixed Escaped variable, safe for HTML output.
 **/
function h($var) {
    if (is_array($var)) {
        return filter_var_array($var, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }
    return htmlspecialchars($var);
}


/**
 * Encode string for URL.
 *
 * @param string $str String to be encoded.
 * @return string Encoded string, suitable for URL.
 **/
function u(string $str) {
    return urlencode($str);
}


/**
 * Append string to URL_ROOT.
 *
 * @param string $url URL path to be appended.
 * @return string Full URL path.
 **/
function url(string $url) {
    $url_root = rtrim(URL_ROOT, '/');
    $url = ltrim($url, '/');
    return $url_root . '/' . $url;
}


/**
 * Var dump variable within HTML <pre> tags.
 *
 * @param mixed $var Variable to be dumped.
 **/
function dump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}


/**
 * Pretty print variable within HTML <pre> tags.
 *
 * @param mixed $var Variable to be printed.
 **/
function printr($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}


/**
 * Var dump variable within HTML <pre> tags and kill script.
 *
 * @param mixed $var Variable to be dumped.
 **/
function dnd($var) {
    dump($var);
    exit;
}


/**
 * Pretty print variable within HTML <pre> tags and kill script.
 *
 * @param mixed $var Variable to be printed.
 **/
function pnd($var) {
    printr($var);
    exit;
}


/**
 * Redirect to another page within the site and kill script.
 *
 * @param string $url Site URL to redirect to, relative to URL_ROOT.
 */
function redirect(string $url) {
    header('Location: ' . url($url));
    exit;
}


/**
 * Redirect back to previous page and kill script.
 */
function back() {
    $url = Session::get('back') ?? URL_ROOT;
    redirect($url);
}


/**
 * Get Request instance.
 *
 * @return Request Request instance.
 */
function request() {
    return Request::instance();
}


/**
 * Retrieve old input value from previous request.
 *
 * If no fields are given, the entire old array is returned.
 * If a string is given, the corrseponding item is returned.
 * If an array is given, an array of items corrseponding to each
 * array element is returned.
 *
 * @param mixed $field Key(s) corresponding to the item(s) to retrieve.
 * @return mixed Item(s) from old array.
 */
function old($field = null) {
    return Request::instance()->old($field);
}


/**
 * Render view.
 *
 * Pass data to view and output to HTML. Values in data array are
 * converted to their own variables. E.g. $data['title'] => 'Welcome'
 * will be passed to the view as $title = 'Welcome'.
 *
 * @param string $view Name of view, relative to app/views directory.
 * @param array $data Array of data to pass to view.
 */
function render(string $view, array $data = [])
{
    // Check for view file.
    if (file_exists(APP_ROOT . "/views/{$view}.php")) {
        // Convert data values to variable variables.
        foreach ($data as $key => $value) {
            $$key = $value;
        }
        // Get errors from session and store on Session class.
        // for subsequent renders.
        $errors = request()->getErrors();
        // Load view.
        require_once APP_ROOT . "/views/{$view}.php";
    } else {
        throw new Exception('View not found.');
    }
}


/**
 * Authorization helper.
 *
 * @param string $method Name of method to run on relevant policy class.
 * @param mixed $model Model instance, or name of model class.
 * @param User $user User instance to send to policy method.
 * @return bool True if authorized; false otherwise.
 * @throws Exception Exception thrown if policy not found.
 */
function can(string $method, $model, User $user = null) {
    if (!Auth::isLoggedIn()) return false;
    if (is_string($model)) {
        $policyClass = $model . 'Policy';
    } else {
        $policyClass = get_class($model) . 'Policy';
    }
    if (!file_exists(APP_ROOT . "/policies/{$policyClass}.php")) {
        throw new Exception('Policy not found.');
    }
    require_once APP_ROOT . "/policies/{$policyClass}.php";
    if (!method_exists($policyClass, $method)) {
        throw new Exception('Policty method does not exist.');
    }
    $user = $user ?? Auth::user();
    if (is_string($model)) {
        return $policyClass::$method($user);
    }
    return $policyClass::$method($user, $model);
}



/**
 * Show 404 not found page and kill script.
 **/
function show_404() {
    require_once APP_ROOT . '/views/404.php';
    exit;
}


/**
 * Return hidden HTML input field for spoofing HTTP verbs.
 *
 * @param string $verb HTTP verb to spoof.
 */
function spoofMethod(string $verb) {
    return '<input type="hidden" name="_method" value="' . strtoupper($verb) . '">';
}


/**
 * Return form validation error message for a given field.
 *
 * @param string $msg Error message to display.
 * @return string Error message as HTML output.
 */
function formError(string $msg)
{
    if (trim($msg) === '') return;
    $html = '<p class="form-error">' . h($msg) . '</p>';
    return $html;
}
