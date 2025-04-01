<?php

use App\utils\Router;
const BASE_PATH = __DIR__. '/../';
require_once BASE_PATH . 'App/utils/functions.php';

spl_autoload_register(function ($class)
{$class= str_replace('\\', DIRECTORY_SEPARATOR, $class);    require basePath("$class.php");});
//var_dump(BASE_PATH);
//exit();

$router=new Router();

$routes = require basePath('routes.php');
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

$router->route($uri, $method);

