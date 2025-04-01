<?php

namespace App\utils;
class Router
{

    public $routes=[];

    private function add(string $method, string $uri, string $controller) : void
    {
        $this->routes[]=['uri'=>$uri, 'controller'=>$controller, 'method'=>strtoupper($method)];
    }
    public function get(string $uri, string $controller) : void
    {
        $this->add('GET', $uri, $controller);
    }
    public function post(string $url, string $controller) : void
    {
        $this->add('POST', $url, $controller);
    }

    public function delete(string $url, string $controller) : void
    {
        $this->add('DELETE', $url, $controller);
    }

    public function patch(string $url, string $controller) : void
    {
        $this->add('PATCH', $url, $controller);
    }

    public function put(string $url, string $controller) : void
    {
        $this->add('PUT', $url, $controller);
    }

    public function route(string $uri, string $method)
    {
        Logger::fine("Resolving route {$uri}");
        foreach($this->routes as $route)
        {
            if( $route['uri']=== $uri && $route['method']===strtoupper($method) )
            {
                return require basePath($route['controller']);
            }
        }
       $this->abort();
    }
    function abort($code=404) : void
    {
        http_response_code($code);
        require basePath("views/{$code}.php");
        die();
    }

}
