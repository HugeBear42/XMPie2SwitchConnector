<?php

/*
	uploadOrderXML.php © 2025 frank@xmpie.com
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
try
{   $db = new Database($configArray['db']); }
catch (Exception $e)
{   Connector::dd($e);  }
$debug = $configArray['debug'];    // If true, script can be run from the command-line & will retrieve XML file from the tmp folder
Logger::setDebug($debug);
Logger::info("------------------------------ Upload orderXML to connector ------------------------------");

$tmpFile=tempnam(sys_get_temp_dir(), uniqid().'.xml');
$xmlContents='';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $xmlContents=file_get_contents("php://input");
    if( stripos($_SERVER["CONTENT_TYPE"], "utf-8") >0 && stripos($xmlContents, '<?xml version="1.0" encoding="utf-16"?>')==0 )	// payload encoded as utf-8 but file will be parsed as utf-16, conversion needed!
    {
        Logger::info("Reencoding XML payload from UTF-8 to UTF-16!");
        $xmlContents = iconv("UTF-8", "UTF-16", $xmlContents );
    }
}
else
{
    Logger::error("This page was accessed in error, request method be POST but got ".$_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    exit;
}
$success=file_put_contents($tmpFile, $xmlContents);
if($success)
{	Logger::info("New order payload received, temporarily stored to $tmpFile ($success bytes)");	}
else
{
    Logger::error("Failed to save file $tmpFile!");
    http_response_code(500);
    exit;
}

$connector=new Connector($db, $configArray);
$order=$connector->registerNewOrderXMLFile($tmpFile);
if ($order!=null) {
    Logger::info("New order payload received from file  $tmpFile!");
} else {
    Logger::error("Failed to save register order file $tmpFile!");
    http_response_code(500);
    exit;
}

$connector->sendToProduction($order);

// Now we generate the JSON file from the OrderXML file

if($configArray['switch']['dataTransfer']=='switchwebhook') // Upload the order JSON to Switch
{
    $status=$connector->sendJSONToSwitch($order);
    $order->updateStatus($status);
}

exit;