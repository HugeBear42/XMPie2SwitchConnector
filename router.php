<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$routes=
    [
        '/switch'=>'../views/index.php',
        '/switch/uploadOrderXML'=>__DIR__.'/APP/controllers/uploadOrderXML.php',                   // called by uStore to upload the orderXML
        '/switch/sendDataToSwitch'=>__DIR__.'/APP/controllers/sendDataToSwitch.php',               // push 'retry' data to Switch webhook
        '/switch/pollDataFromSwitch'=>__DIR__.'/APP/controllers/getXMPieOrderData.php',            // Switch pulls the data from the connector
        '/switch/updateXMPieOrderStatus'=>__DIR__.'/APP/controllers/updateXMPieOrderStatus.php',   // Push the order status to uStore
        '/switch/switchSimulator'=> __DIR__ . '/public/test/switchSimulator.php'                   // a dummy endpoint that dumps header & contents to the screen!

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
