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
 * Show 404 not found page and kill script.
 **/
function show_404() {
    require_once APP_ROOT . '/views/404.php';
    exit;
}


/**
 * Redirected unauthenticated users and flash error message.
 */
function denyAuthRestricted() {
    Session::flash('error', 'Must be logged in.');
    redirect('/login');
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
 * Convert namespaced class into a path.
 *
 * @param string $class Class namespace to be converted.
 * @return string Path in lowercase with uppercased first letter of class.
 */
function namespaceToPath($class) {
    $pathParts = explode('\\', $class);
    $className = array_pop($pathParts);
    $className = ucfirst($className);
    array_push($pathParts, $className);
    $path = strtolower(implode('/', $pathParts));
    return $path;
}
