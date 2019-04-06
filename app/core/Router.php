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


    /** @var Route $route Matching route for the request. */
    private static $route;


    /** @var string $cacheFilename Name of file to cache. */
    private static $cacheFilename;


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
        // Instantiate Request object.
        $request = new Request;

        // Get requested URL.
        $url = $request->getUrl();

        // Get request method.
        $requestMethod = $request->method();

        // Check request method is a valid method.
        if (!array_key_exists($requestMethod, self::$routes)) {
            show_404();
        }

        // Get reference to relevant sub-array in routes array.
        $routes =& self::$routes[$requestMethod];

        // Check if any routes match the URL.
        foreach ($routes as $route) {
            if (preg_match($route->getRoute(), $url, $params)) {
                // Store route as static property.
                self::$route = $route;

                // Take URL out of $params array, leaving only regex captured groups.
                // This array can then be passed to the relevant action as parameters.
                array_shift($params);

                // Run middleware registered to route.
                $route->runCallback($params);

                // If GET request matches a registered route,
                // store url in session to allow redirecting back in
                // from the next request (e.g. for processing invalid forms).
                if ($request->isGet()) {
                    Session::set('back', $url);
                }

                // Cache output if requested.
                if (isset(self::$cacheFilename)) {
                    self::cacheOutput();
                }

                // If route was found, exit script after running appropriate callback.
                exit;
            }
        }

        // If no route was found, show 404 not found page.
        show_404();
    }


    /**
     * Register route.
     *
     * @param string $method Rquest method for which to register route.
     * @param Route $route Route to register.
     */
    public static function registerRoute(string $method, Route $route)
    {
        self::$routes[$method][] = $route;
    }


    /**
     * Tell router to cache output when
     */
    public static function cache(int $duration = 60 * 60 * 24)
    {
        $filename = self::$route->getCallback();
        self::$cacheFilename = $filename . '.' . $duration .  '.html';
    }


    /**
     * Cache output for the given duration.
     *
     * Check if $cacheFilename is set and if so, cache output
     * with the name $cacheFilename.html in the /app/cache directory.
     **/
    private static function cacheOutput()
    {
        // Get basename before saving for security.
        $filename = basename(self::$cacheFilename);
        file_put_contents(APP_ROOT . "/cache/$filename", ob_get_contents());
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
     * @param string $callback Callback to check cache output for.
     * @return bool True if file found and not outdated; false otherwise.
     */
    public static function checkCache(string $callback)
    {
        $callback = str_replace('\\', '_', $callback);
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
    public static function serveFromCache()
    {
        require_once APP_ROOT . '/cache/' . basename(self::$cachedFile);
    }
}
