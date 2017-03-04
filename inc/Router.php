<?php

class Router {
    /**
     * Process URI
     * Processes the current URIs with the given array.
     *
     * @param array $routes Array of routes for different URIs
     *
     * @return bool
     */
    public static function process($routes) {
        $requestURI = self::preProcess();

        if (self::checkURI() === false) {
            return true;
        }

        foreach ($routes as $route) {
            if ($route['method'] === strtolower(filter_input(INPUT_SERVER, 'REQUEST_METHOD'))) {
                $matched = preg_match('/^' . $route['uri'] . '$/', $requestURI, $matches);

                if ($matched === 1) {
                    call_user_func($route['func'], $matches);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Pre-Process
     * Pre-processes the REQUEST_URI for use with static::process.
     *
     * @return string Processed URI
     */
    private static function preProcess() {
        $requestURI = substr(filter_input(INPUT_SERVER, 'REQUEST_URI'), strlen(BASEURI));
        $requestURI = strtok($requestURI, '?');

        return $requestURI;
    }

    /**
     * Remove Slash
     * Removes the trailing slash of a URL and redirects the user.
     *
     * @return bool False if check fails.
     */
    private static function checkURI() {
        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI');

        if ($uri === BASEURI) {
            return true;
        }

        if (preg_match('/^(.+)(\/)(|\?.+)$/', $uri, $matches) === 1) {
            $uri = $matches[1] . $matches[3];

            header('Location: ' . $uri);

            return false;
        }

        return true;
    }
}
