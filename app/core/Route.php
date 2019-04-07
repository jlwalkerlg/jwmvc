<?php

/**
 * Route class.
 */
class Route
{
    /** @var string $middleware Name of middleware to run. */
    private $middleware;

    /** @var string $pattern Pattern to search for when converting route to regex. */
    private $pattern = '/{[^{}]+}/';

    /** @var string $regex Pattern to replace route params with for matching against URL. */
    private $regex = '(\w+)';

    /** @var string $route Registered route, with slashes trimmed off. */
    private $route;

    /** @var string $callback Registered callback in the form Controller@action. */
    private $callback;

    /**
     * Store route pattern and callback.
     *
     * @param string $route URL pattern to register for this route.
     * @param mixed $callback Callback to call when URL matches this route. Controller@action string or function.
     */
    public function __construct(string $route, $callback)
    {
        // Store URL route.
        $this->route = trim($route, '/');

        // Store callback.
        $this->callback = $callback;
    }


    /**
     * Retrieve route as regex.
     *
     * @return string Regex form of route.
     */
    public function getRoute()
    {
        $route = preg_replace($this->pattern, $this->regex, $this->route);
        return "~^$route$~";
    }


    /**
     * Retrieve callback.
     *
     * @return string Callback registered to route.
     */
    public function getCallback()
    {
        return $this->callback;
    }


    /**
     * Store middleware class name on route.
     *
     * @param string $middleware Middleware to add.
     * @return Route Current route instance.
     */
    public function middleware(string $middleware)
    {
        $this->middleware = $middleware;
        return $this;
    }


    /**
     * Run middleware if registered.
     */
    public function runMiddleware()
    {
        if (isset($this->middleware)) {
            $middleware = ucfirst($this->middleware);
            if (!file_exists(APP_ROOT . "/middleware/$middleware.php")) {
                throw new Exception('Middleware not found.');
            }
            require_once APP_ROOT . "/middleware/$middleware.php";
            $middleware::run();
        }
    }


    /**
     * Run callback registered to the route.
     */
    public function runCallback(array $params = [])
    {
        // Run middleware.
        $this->runMiddleware();

        // Run callback.
        if (is_callable($this->callback)) {
            call_user_func_array($this->callback, $params);
        } else {
            // If output has been cached for callback, serve it.
            if (Router::checkCache($this->callback)) {
                Router::serveFromCache();
                return;
            }

            // Parse Controller and action from callback.
            $atIndex = strpos($this->callback, '@');
            $controller = substr($this->callback, 0, $atIndex);
            $action = substr($this->callback, $atIndex + 1);

            // Load controller.
            require_once APP_ROOT . "/controllers/{$controller}.php";

            // Instantiate controller.
            $controller = new $controller;

            // Call controller action, passing in any params.
            call_user_func_array([$controller, $action], $params);
        }
    }


    /**
     * Impose regex on route parameters.
     *
     * @param mixed $wheres Parameter(s) and regex condition(s) to impose.
     * @return Route Current route instance.
     */
    public function where(...$clauses)
    {
        if (!is_array(current($clauses))) {
            $param = $clauses[0];
            $regex = $clauses[1];
            $this->imposeRegex($param, $regex);
        } else {
            foreach ($clauses as $clause) {
                $param = array_keys($clause)[0];
                $regex = current($clause);
                $this->imposeRegex($param, $regex);
            }
        }
        return $this;
    }


    /**
     * Impose regex on route param.
     *
     * @param string $param The name of the parameter in the route to replace.
     * @param string $regex The regex string to replace the param with.
     */
    private function imposeRegex(string $param, string $regex)
    {
        $param = '{'.$param.'}';
        $regex = '('.$regex.')';
        $this->route = str_replace($param, $regex, $this->route);
    }


    /**
     * Register route for GET requests.
     *
     * @param string $route URL to match before calling callback
     * @param mixed $callback Function or Controller@action to call if URL matches $route.
     *
     * @return Route Instance of the Route class.
     */
    public static function get(string $route, $callback)
    {
        $route = new self($route, $callback);
        Router::registerRoute('GET', $route);
        return $route;
    }


    /**
     * Register route for POST requests.
     *
     * @param string $route URL to match before calling callback
     * @param mixed $callback Function or Controller@action to call if URL matches $route.
     *
     * @return Route Instance of the Route class.
     */
    public static function post(string $route, $callback)
    {
        $route = new self($route, $callback);
        Router::registerRoute('POST', $route);
        return $route;
    }


    /**
     * Register route for PUT requests.
     *
     * @param string $route URL to match before calling callback
     * @param mixed $callback Function or Controller@action to call if URL matches $route.
     *
     * @return Route Instance of the Route class.
     */
    public static function put(string $route, $callback)
    {
        $route = new self($route, $callback);
        Router::registerRoute('PUT', $route);
        return $route;
    }


    /**
     * Register route for PATCH requests.
     *
     * @param string $route URL to match before calling callback
     * @param mixed $callback Function or Controller@action to call if URL matches $route.
     *
     * @return Route Instance of the Route class.
     */
    public static function patch(string $route, $callback)
    {
        $route = new self($route, $callback);
        Router::registerRoute('PATCH', $route);
        return $route;
    }


    /**
     * Register route for DELETE requests.
     *
     * @param string $route URL to match before calling callback
     * @param mixed $callback Function or Controller@action to call if URL matches $route.
     *
     * @return Route Instance of the Route class.
     */
    public static function delete(string $route, $callback)
    {
        $route = new self($route, $callback);
        Router::registerRoute('DELETE', $route);
        return $route;
    }


    /**
     * Register routes for a number of request methods.
     *
     * @param array $verbs List of HTTP verbs with which to register the route
     * @param string $route URL to match before calling callback
     * @param mixed $callback Function or Controller@action to call if URL matches $route.
     *
     * @return Route Instance of the Route class.
     */
    public static function match(array $verbs, string $route, $callback)
    {
        $route = new self($route, $callback);
        foreach ($verbs as $verb) {
            $verb = strtoupper($verb);
            Router::registerRoute($verb, $route);
        }
        return $route;
    }


    /**
     * Register route for all request methods.
     *
     * @param string $route URL to match before calling callback
     * @param mixed $callback Function or Controller@action to call if URL matches $route.
     *
     * @return Route Instance of the Route class.
     */
    public static function any(string $route, $callback)
    {
        $route = new self($route, $callback);
        $verbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        foreach ($verbs as $verb) {
            Router::registerRoute($verb, $route);
        }
        return $route;
    }
}
