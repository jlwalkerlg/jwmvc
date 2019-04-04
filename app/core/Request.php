<?php

/**
 * Core Request class.
 *
 * Features:
 * - methods for accessing input values
 * - instantiates uploaded files
 * - inbuilt CSRF protection
 * - method for storing input data through to subsequent request
 */
class Request
{
    /** @var Request $instance Instance of Request class. */
    private static $instance;

    /** @var string $url Requested URL. */
    private $url;

    /** @var array $input All GET/POST input fields. */
    private $input;

    /** @var array $files All uploaded files, instantiated as a FileUpload instance. */
    private $files = [];

    /** @var array $old All input fields saved from previous request. */
    private $old;


    /**
     * Get instance.
     *
     * @return Request Request instance.
     */
    public static function instance()
    {
        return self::$instance;
    }


    /**
     * Instantiate uploaded files and store all input.
     **/
    public function __construct()
    {
        // Save instance as static class property.
        self::$instance = $this;

        // Set requested URL.
        $this->url = trim( $_GET['url'] ?? '', '/');

        // Merge and store all input.
        $this->input = array_merge($_GET, $_POST, $_FILES);

        // If request is a POST request, check CSRF token.
        // If CSRF token is invalid, save form input and redirect back to form.
        if ($this->isPost() && !CSRF::validateToken()) {
            $this->save();
            back();
        }

        // Instantiate and store uploaded files.
        foreach ($_FILES as $key => $file) {
            $this->files[$key] = new FileUpload($file);
        }
        // Store all old input.
        $this->old = Session::getAndUnset('old');
    }


    /**
     * Retrieve item(s) from input array.
     *
     * If no fields are given, the entire input array is returned.
     * If a string is given, the corrseponding item is returned.
     * If an array is given, an array of items corrseponding to each
     * array element is returned.
     *
     * @param mixed $field Key(s) corresponding to the item(s) to retrieve.
     * @return mixed Item(s) from input array, or null if not found.
     **/
    public function input($field = null)
    {
        return $this->retrieve('input', $field);
    }


    /**
     * Retrieve file(s) from files array.
     *
     * If no fields are given, the entire file array is returned.
     * If a string is given, the corrseponding item is returned.
     * If an array is given, an array of files corrseponding to each
     * array element is returned.
     *
     * @param mixed $field Key(s) corresponding to the file(s) to retrieve.
     * @return mixed File(s) from files array, or null if not found.
     */
    public function file($field = null)
    {
        return $this->retrieve('files', $field);
    }


    /**
     * Retrieve item(s) from old array.
     *
     * If no fields are given, the entire old array is returned.
     * If a string is given, the corrseponding item is returned.
     * If an array is given, an array of items corrseponding to each
     * array element is returned.
     *
     * @param mixed $field Key(s) corresponding to the item(s) to retrieve.
     * @return mixed Item(s) from old array, or null if not found.
     **/
    public function old($field = null)
    {
        return $this->retrieve('old', $field);
    }


    /**
     * Retrieve item(s) from input, files, or old arrays.
     *
     * If no fields are given, the entire relevant array is returned.
     * If a string is given, the corrseponding item is returned.
     * If an array is given, an array of items corrseponding to each
     * array element is returned.
     *
     * @param mixed $field Key(s) corresponding to the item(s) to retrieve.
     * @return mixed Item(s) from relevant array, or null if not found.
     **/
    private function retrieve($arr, $field = null)
    {
        if (!isset($field)) {
            return $this->$arr;
        }
        if (is_string($field)) {
            return $this->$arr[$field] ?? null;
        }
        if (is_array($field)) {
            $result = [];
            foreach ($field as $key) {
                $result[] = $this->$arr[$key] ?? null;
            }
            return $result;
        }
    }


    /**
     * Save input items through to next request.
     *
     * @param mixed $field Keys corresponding to the inputs items to save.
     */
    public function save($field = null)
    {
        if (!isset($field)) {
            Session::set('old', $this->input);
        }
        if (is_string($field)) {
            Session::set('old', $this->input[$field]);
        }
        if (is_array($field)) {
            $items = [];
            foreach ($field as $key) {
                $items[$key] = $this->input[$key];
            }
            Session::set('old', $items);
        }
    }


    /**
     * Get request method.
     *
     * PUT, PATCH, and DELETE methods must be spoofed
     * in forms using the spoofMethod() helper function.
     *
     * @return string Request method in uppercase.
     **/
    public function method()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return 'GET';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['_method'])) {
                return 'POST';
            }
            return strtoupper($_POST['_method']);
        }
    }


    /**
     * Check request method is GET.
     *
     * @return bool True if request method is GET; false otherwise.
     */
    public function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }


    /**
     * Check request method is POST.
     *
     * @return bool True if request method is POST; false otherwise.
     */
    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }


    /**
     * Retrieve requested URL.
     */
    public function getUrl()
    {
        return $this->url;
    }
}
