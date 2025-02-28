<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$routes=
    [
        '/'=>'../views/index.php',
        '/uploadOrderXML'=>__DIR__.'/APP/controllers/uploadOrderXML.php',                   // called by uStore to upload the orderXML
        '/sendDataToSwitch'=>__DIR__.'/APP/controllers/sendDataToSwitch.php',               // push 'retry' data to Switch webhook
        '/pollDataFromSwitch'=>__DIR__.'/APP/controllers/getXMPieOrderData.php',            // Switch pulls the data from the connector
        '/updateXMPieOrderStatus'=>__DIR__.'/APP/controllers/updateXMPieOrderStatus.php',   // Push the order status to uStore
        '/switchSimulator'=> __DIR__ . '/public/test/switchSimulator.php'                   // a dummy endpoint that dumps header & contents to the screen!

    ];



function routeToController(string $uri, array $routes) : void
{
    if(array_key_exists($uri, $routes)) {
        //echo "found route $uri!" ;
        require $routes[$uri];
    }
    else {
        error_log("No such route: $uri");
        abort();
    }
}

function abort($code=404) : void
{
    http_response_code($code);
    require "../views/$code.php";
}

routeToController($uri, $routes);

//dd($uri).'<br>';
//$array=parse_url($uri);
//dd($array);
//dd($_SERVER);