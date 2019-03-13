<?php

/**
* Core router class.
*/
class Router
{
    /** @var array $routes Array of routes for each HTTP verb.
     *
     * Each route is a string, which may or may not contain regex,
     * to be matched against the incoming URL.
     */
    private static $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => []
    ];


    /** @var string $callback Controller and method to call.
     *
     * Set when the URL is parsed and is matched with a registered route.
     */
    private static $callback;


    /** @var string $cachedFile Name of cached file.
     *
     * Set if the URL requests a controller method which has previously
     * stored its output in the cache.
     */
    private static $cachedFile;


    /**
     * Route the incoming URL.
     */
    public static function route()
    {
        // Get URL from query string.
        $url = $_GET['url'] ?? '';
        $url = rtrim($url, '/');

        // Get request method and check it is a valid method.
        $requestMethod = self::getRequestMethod();
        if (!array_key_exists($requestMethod, self::$routes)) {
            show_404();
        }

        // If request method is a GET, set the requested URL in
        // the session so controllers can redirect back to
        // the previous page if desired.
        // E.g. forms handling POST requests can redirect back to
        // the form if there are errors.
        if ($requestMethod === 'GET') {
            Session::set('back', $url);
        }

        // Get reference relevant sub-array in routes array.
        $routes =& self::$routes[$requestMethod];

        // Check if the URL is in the relevent routes array.
        foreach ($routes as $route => $callback) {
            if (preg_match($route, $url, $params)) {
                // Store callback as a static class property.
                self::$callback = $callback;

                // If file is in cache and is not expired, serve immediately.
                if (self::checkCache()) {
                    self::serveFromCache();
                }

                // Take URL out of $params array, leaving only regex captured groups.
                // This array can then be passed to the relevant method as parameters.
                array_shift($params);

                // Parse callback for controller and method.
                $atIndex = strpos($callback, '@');
                $controller = substr($callback, 0, $atIndex);
                $method = substr($callback, $atIndex + 1);

                // Get path to controller relative to app/controllers directory.
                // If controller is nested in a subdirectory, it should be defined
                // in the routes with backslashes.
                $controllerPath = namespaceToPath($controller);

                // Load controller.
                require_once APP_ROOT . "/controllers/$controllerPath.php";

                // Get controller name from end of namespace.
                $controller = explode('\\', $controller);
                $controller = end($controller);

                // Instantiate controller.
                $controller = new $controller;

                // Check if method is restricted to authenticated users.
                if (in_array($method, $controller::getAuthBlacklist(), true)) {
                    // Deny access if method is restricted and user is not authenticated.
                    if (!Session::isLoggedIn()) {
                        denyAuthRestricted();
                    }
                }

                // Call controller method, passing in any params.
                call_user_func_array([$controller, $method], $params);

                // If route was found, exit script after running appropriate callback.
                exit;
            }
        }

        // If no route was found, show 404 not found page.
        show_404();
    }


    /**
     * Return the appropriate request method.
     * PUT, PATCH, and DELETE requests are detected by
     * a hidden form input.
     */
    private static function getRequestMethod()
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
     * Register route for GET requests.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     */
    public static function get(string $route, string $callback)
    {
        $route = self::parseRoute($route);
        self::$routes['GET'][$route] = $callback;
    }


    /**
     * Register route for POST requests.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     */
    public static function post(string $route, string $callback)
    {
        $route = self::parseRoute($route);
        self::$routes['POST'][$route] = $callback;
    }


    /**
     * Register route for PUT requests.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     */
    public static function put(string $route, string $callback)
    {
        $route = self::parseRoute($route);
        self::$routes['PUT'][$route] = $callback;
    }


    /**
     * Register route for PATCH requests.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     */
    public static function patch(string $route, string $callback)
    {
        $route = self::parseRoute($route);
        self::$routes['PATCH'][$route] = $callback;
    }


    /**
     * Register route for DELETE requests.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     */
    public static function delete(string $route, string $callback)
    {
        $route = self::parseRoute($route);
        self::$routes['DELETE'][$route] = $callback;
    }


    /**
     * Register routes for a number of request methods.
     *
     * @param array $verbs List of HTTP verbs with which to register the route
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     */
    public static function match(array $verbs, string $route, string $callback)
    {
        $route = self::parseRoute($route);
        foreach ($verbs as $verb) {
            $verb = strtoupper($verb);
            self::$routes[$verb][$route] = $callback;
        }
    }


    /**
     * Register route for all request methods.
     *
     * @param string $route URL to match before calling callback
     * @param string $callback Controller@action to call if URL matches $route
     */
    public static function any(string $route, string $callback)
    {
        $route = self::parseRoute($route);
        foreach (self::$routes as &$routes) {
            $routes[$route] = $callback;
        }
    }


    // Convert routes to a regex pattern by replacing any {params} with
    // a regex group that will match one or more word characters.
    // This route is then used as a regex pattern to test against
    // the URL typed in by the user.
    /**
     * Replace any {param} in the route with the regex
     * pattern (\w+), which will match one or more word
     * characters ([A-Za-z0-9_]).
     *
     * @return string Pattern to match against URL
     */
    private static function parseRoute(string $route)
    {
        $route = ltrim($route, '/');
        $route = preg_replace('/{[^{}]+}/', '(\w+)', $route);
        $route = "~^$route$~";
        return $route;
    }


    /**
     * Check cache for requested file.
     *
     * Checks cache for file relating to requested callback.
     * Cached files are named with the format Controller@action.time.html
     * where Controller is has underscores in place of backslashes.
     *
     * If an appropriate file is found but is outdated, it is deleted
     * from the cache.
     *
     * If an appropriate file is found, it is stored on the class.
     *
     * @return bool True if file found and not outdated; false otherwise.
     */
    private static function checkCache()
    {
        $callback = str_replace('\\', '_', self::$callback);
        $pattern = '/' . $callback . '(\.[0-9]+)?\.html/';
        $cachedFiles = scandir(APP_ROOT . '/cache/');

        foreach ($cachedFiles as $filename) {
            // Does the filename match the search pattern?
            if (preg_match($pattern, $filename)) {

                // Is the cached file expired?
                $cacheDuration = (int) explode('.', $filename)[1];
                $now = (int) time();
                $lastModified = filemtime(APP_ROOT . '/cache/' . $filename);

                if ($cacheDuration > $now - $lastModified) {
                    // Cached file not expired; store name of cached file and return true.
                    self::$cachedFile = $filename;
                    return true;
                }

                // Cached file expired: delete it and return false.
                unlink(APP_ROOT . '/cache/' . basename($filename));
            }
        }

        // Cached filed not found or expired -- return false;
        return false;
    }



    /**
     * Served cached file.
     */
    private static function serveFromCache()
    {
        require_once APP_ROOT . '/cache/' . basename(self::$cachedFile);
        exit;
    }


    /**
     * Get callback (Controller@action) registered for matching route
     */
    public static function getCallback()
    {
        return self::$callback;
    }
}
