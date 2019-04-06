<?php

/**
 * Route class.
 */
class Route
{
    /** @var string $pattern Pattern to serarch for when converting route to regex. */
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
     * @param string $callback Controller@action callback to call when URL matches this route.
     */
    public function __construct(string $route, string $callback)
    {
        $this->route = trim($route, '/');
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
     * Impose regex on route parameters.
     *
     * @param mixed $wheres Parameter(s) and regex condition(s) to impose.
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
     * @param string $callback Controller@action to call if URL matches $route
     *
     * @return Route Instance of the Route class.
     */
    public static function get(string $route, string $callback)
    {
        $route = new self($route, $callback);
        Router::registerRoute('GET', $route);
        return $route;
    }


    /**
     * Register route for POST requests.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     *
     * @return Route Instance of the Route class.
     */
    public static function post(string $route, string $callback)
    {
        $route = new self($route, $callback);
        Router::registerRoute('POST', $route);
        return $route;
    }


    /**
     * Register route for PUT requests.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     *
     * @return Route Instance of the Route class.
     */
    public static function put(string $route, string $callback)
    {
        $route = new self($route, $callback);
        Router::registerRoute('PUT', $route);
        return $route;
    }


    /**
     * Register route for PATCH requests.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     *
     * @return Route Instance of the Route class.
     */
    public static function patch(string $route, string $callback)
    {
        $route = new self($route, $callback);
        Router::registerRoute('PATCH', $route);
        return $route;
    }


    /**
     * Register route for DELETE requests.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     *
     * @return Route Instance of the Route class.
     */
    public static function delete(string $route, string $callback)
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
     * @param string $callback Controller@action to call if URL matches $route
     *
     * @return Route Instance of the Route class.
     */
    public static function match(array $verbs, string $route, string $callback)
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
     * @param string $callback Controller@action to call if URL matches $route
     *
     * @return Route Instance of the Route class.
     */
    public static function any(string $route, string $callback)
    {
        $route = new self($route, $callback);
        $verbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        foreach ($verbs as $verb) {
            Router::registerRoute($verb, $route);
        }
        return $route;
    }
}
