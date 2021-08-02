<?php

namespace tpab\Router;

class RouteCollection
{
    private $routes = array();

    public function addRoute(Route $route)
    {
        $found_route = $this->findRoute($route->path());
        if ($found_route) {
            $methods = $route->methods();
            $callback = $route->callback($methods[0]);
            $callback_params = $route->callbackParams($methods[0]);
            $this->addRouteMethods($found_route, $methods, $callback, $callback_params);
        } else {
            $this->routes[] = $route;
        }
    }

    private function addRouteMethods($route, $methods, $callback, $callback_params)
    {
        $route->addMethods($methods, $callback, $callback_params);
    }

    public function findRoute($path)
    {
        $found = array_filter($this->routes, function ($route) use ($path) {
            preg_match($route->regex(), $path, $matches);
            if (!empty($matches[0])) {
                return true;
            }
            return false;
        });
        return reset($found);
    }

    public function resolveRoute($method, $path, $groups = [])
    {
        $status = RouteResolved::PATH_NOT_FOUND;
        $method = strtoupper($method);
        $path = strlen($path) > 1 ? rtrim($path, '/') : $path;

        if ($this->hasRoute($path)) {
            $route = $this->findRoute($path);
            $status = RouteResolved::METHOD_NOT_ALLOWED;
            $allowed_methods = $route->methods();

            if (in_array($method, $route->methods())) {
                $status = RouteResolved::FOUND;
                $callback = $route->callback($method);
                $path_params = $this->resolveParams($path, $route);
                $callback_params = $route->callbackParams($method);
            }
        } else {
            
            foreach ($groups as $group) {
                if ($group->hasRoute($method, $path)) {
                    return $group->hasRoute($method, $path);
                }
            }
        }
        $resolved = compact('status', 'method', 'path', 'allowed_methods', 'path_params', 'callback', 'callback_params');

        return new RouteResolved($resolved);
    }

    private function resolveParams($path, $route)
    {
        $parts = explode('/', ltrim($path, '/'));
        $path_params = [];

        foreach ($route->parts() as $key => $value) {
            if ($parts[$key] !== $value) {
                $path_params[$value] = $parts[$key];
            }
        };
        return $path_params;
    }

    public function hasRoute($path)
    {
        return !empty($this->findRoute($path));
    }

    public function findRoutesWithParameters()
    {
        return array_filter($this->routes, function ($route) {
            if ($route->parameters() !== []) {
                return true;
            }
            return false;
        });
    }
}
