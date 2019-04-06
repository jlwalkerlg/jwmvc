<?php

/**
 * Core controller class.
 */
class Controller
{
    /** @var array $errors Array of errors retrieved from session. */
    protected $errors;

    /** @var string $cacheFilename Name of file to store in cache. */
    protected $cacheFilename;


    /**
     * Cache output of controller when it has finished executing.
     */
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
        // Require model.
        require_once APP_ROOT . "/models/{$model}.php";

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
            // Get errors from session and store on object for subsequent renders.
            if (!isset($this->errors)) {
                $this->errors = Session::getAndUnset('errors') ?? [];
            }
            $errors = $this->errors;
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
        $filename = Router::getCallback();
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
     * Run validations on item.
     *
     * Runs all specified validations against $_POST and $_FILES. If all valiations passed,
     * the validated property on the controller instance is changed to true. Otherwise,
     * a list of valiations errors are saved on the controller instance.
     *
     * @param array $validations Array of validations to run against model instance.
     */
    public function validate(array $validations)
    {
        $validator = new Validator($validations);
        if (!$validator->run()) {
            request()->save();
            Session::set('errors', $validator->getErrors());
            back();
        }
    }
}
