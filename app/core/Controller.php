<?php

/**
 * Core controller class.
 */
class Controller
{
    /** @var array $auth_blacklist List of all methods.
     *
     * List of all methods on current controller to restrict to
     * authenticated (logged in) users only. Gets checked by the router
     * before calling the method.
     */
    protected static $auth_blacklist = [];


    /** @var string $cacheFilename Name of file to store in cache. */
    protected $cacheFilename;


    /** @var bool $validated True if all validations pass successfully; false otherwise.  */
    protected $validated = false;


    /** @var array $validationErrors List of validation errors from any failed validations. */
    protected $validationErrors = [];


    public function __destruct()
    {
        $this->cacheOutput();
    }


    /**
     * Return instantiated model instance.
     *
     * @param string $model Name of model to instantiate.
     * @return object New model instance.
     */
    public function model(string $model)
    {
        $path = namespaceToPath($model);

        // Require model.
        require_once APP_ROOT . "/models/{$path}.php";

        // Instantiate model.
        return new $model;
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
    public function render(string $view, array $data = [])
    {
        // Check for view file
        if (file_exists(APP_ROOT . "/views/{$view}.php")) {
            // Convert data values to variable variables.
            foreach ($data as $key => $value) {
                $$key = $value;
            }
            // Load view.
            require_once APP_ROOT . "/views/{$view}.php";
        } else {
            // Show 404 not found page if view not found.
            show_404();
        }
    }


    /**
     * Tell controller to cache output for the given duration.
     */
    protected function cache(int $duration = 60 * 60 * 24)
    {
        $callback = Router::getCallback();
        $filename = str_replace('\\', '_', $callback);
        $this->cacheFilename = $filename . '.' . $duration .  '.html';
    }


    /**
     * Cache output if asked to.
     *
     * Check if $cacheFilename is set and if so, cache output
     * with the name $cacheFilename.html in the /app/cache directory.
     **/
    private function cacheOutput()
    {
        if (!is_null($this->cacheFilename)) {
            // Get basename before saving for security.
            $this->cacheFilename = basename($this->cacheFilename);
            file_put_contents(APP_ROOT . '/cache/' . $this->cacheFilename, ob_get_contents());
        }
    }


    /**
     * Get list of methods on current controller restricted to authenticated users.
     */
    public static function getAuthBlacklist()
    {
        return static::$auth_blacklist;
    }


    /**
     * Run validations on item.
     *
     * Runs all specified validations against the item. If all valiations passed,
     * the validated property on the controller instance is changed to true. Otherwise,
     * a list of valiations errors are saved on the controller instance.
     *
     * @param array $item Array to be validated.
     * @param array $validations Array of validations to run against item.
     */
    public function validate(array $item, array $validations)
    {
        $this->validated = false;
        $validator = new Validator($item, $validations);
        if ($validator->run()) {
            $this->validated = true;
        } else {
            $this->validationErrors = $validator->getErrors();
        }
    }
}
