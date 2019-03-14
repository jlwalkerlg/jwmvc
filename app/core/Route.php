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
}
