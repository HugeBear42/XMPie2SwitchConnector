<?php

/*
	testUploadXML2Connector.php © 2025 frank@xmpie.com
	A test endpoint that uploads a uStore XML file to the connector

	v1.00 of 2025-01-30	Genesis

*/

use App\controllers\Connector;
use App\utils\Database;
use App\utils\Logger;

// Setup a simple PHP autoloader
spl_autoload_register(function ($class) {$path=__DIR__.'/../../'.str_replace('\\', '/', $class).'.php'; require $path;});

$configArray = require __DIR__ . '/../../App/config/appConfig.php';
$db=null;
try {   $db = new Database($configArray['db']);    }
catch (Exception $e)
{   Connector::dd($e);  }
$debug = $configArray['debug'];    // If true, script can be run from the command-line & will retrieve XML file from the tmp folder
Logger::setDebug($debug);
$testFile=__DIR__."/../../Data/samples/43235.xml";
$connector=new Connector($db, $configArray);
$order=$connector->registerNewOrderXMLFile($testFile);

if (is_object($order)) {
    Logger::info("New order payload received from file  $testFile!");
} else {
    Logger::error("Failed to save registered order file $testFile!");
    http_response_code(500);
    exit;
}

// Now we generate the JSON file from the OrderXML file
$status=$connector->sendJSONToSwitch($order);
$order->updateStatus($status);


exit;