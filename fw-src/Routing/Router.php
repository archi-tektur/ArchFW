<?php declare(strict_types=1);

namespace ArchFW\Routing;

use ArchFW\Exceptions\Routing\GeneralRoutingException;
use ArchFW\Exceptions\Routing\RouteNotFoundException;
use ArchFW\Utilities\UriParser;

class Router implements RouterInterface
{
    /** @var array */
    private $requestGetVars;

    /** @var string */
    private $safeZone;

    /** @var string */
    private $uri;

    /** @var Route[] */
    private $routes;

    /**
     * Router
     *
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        $this->uri = $this->handleGets($uri);

        // load routes config
        $routesCfg = RoutesLoader::loadOne('../config/routing.json');

        // assign safe zone
        if (!array_key_exists('safe-zone', $routesCfg)) {
            throw new GeneralRoutingException('Config has no safe zone settings.');
        }

        // parse all routes
        $routeParser = new RoutesParser($routesCfg);
        $this->routes = $routeParser->parseAll();
    }

    /**
     * Matches route
     *
     * @return Route
     */
    public function matchRoute(): Route
    {
        $key = $this->uri;

        // find the one valid route
        /** @var Route[] $route */
        $route = array_values(array_filter($this->routes, static function ($route) use ($key) {
            /** @var Route $route */
            return $route->getPath() === $key;
        }));

        // catch route not found
        if (!array_key_exists(0, $route) || !$route[0] instanceof Route) {
            $message = sprintf('Route not found for \'%s\'', $this->uri);
            throw new RouteNotFoundException($message);
        }

        // catch multiple definitions for same route
        if (count($route) > 1) {
            $message = sprintf('Multiple definitions for route \'%s\'', $this->uri);
            throw new RouteNotFoundException($message);
        }

        return $route[0];
    }

    private function handleGets(string $uri)
    {
        // parse get variables
        $exploded = explode('?', $uri);
        $this->requestGetVars = array_key_exists(1, $exploded) ? UriParser::getVariables($exploded[1]) : [];

        // return router-able uri
        return $exploded[0];
    }

    /**
     * @return array
     */
    public function getRequestGetVars(): array
    {
        return $this->requestGetVars;
    }

    public function getSafeZone(): string
    {
        return $this->safeZone;
    }

    public function getAdressForRoute(string $routeName)
    {
        // TODO
    }
}
