<?php
/*
	endpoint.php © 2024-2025 frank@xmpie.com
	The endpoint used by Enfocus Switch to poll for new orders
	An order polling request body is formatted as follows: { "action" : "polling",  "jobType" : "new", "limit" : 10 }

	
	v1.00 of 2024-11-27	Genesis
	
*/

use App\utils\Database;
use App\utils\Logger;
use App\controllers\Order;
use App\controllers\Connector;

spl_autoload_register(function ($class) {$path= __DIR__ . '/../../' .str_replace('\\', '/', $class).'.php'; require $path;});	// Setup a simple PHP autoloader

function validatePollingRequest(object $obj) : bool
{	return property_exists( $obj, 'action') && $obj->action==='polling' && property_exists($obj, 'jobType') && in_array($obj->jobType, Order::STATUS_ARRAY);	}

$configArray= require __DIR__ . '/../config/appConfig.php';
$db=null;
try
{   $db=new Database($configArray['db']);  }
catch (Exception $e)
{   Connector::dd($e);  }
$connector = new Connector($db, $configArray);
if( !$connector->checkAuthentication() )
{
    $connector->printErrorMessage("Authentication failed", 401);
    exit;
}

$debug=$configArray['debug'];	// If true, script can be run from the command-line & will retrieve XML file from the tmp folder
Logger::setDebug($debug);
Logger::fine(print_r(getallheaders(), true));
Logger::fine(print_r($_SERVER, true));


$str=file_get_contents("php://input");	// get the JSON contents
Logger::info("--------------------------- start Switch polling request ---------------------------");
Logger::fine("Received payload from Switch: ".(empty($str) ? "[empty payload]" : $str));
$pollingRequest=null;
if(strlen($str)>0)
{
	$pollingRequest=json_decode($str, false);	// Return as an object or null!
}
if( ! (is_object($pollingRequest) && validatePollingRequest($pollingRequest)) )	// if not a valid object, abort!
{
	$connector->printErrorMessage('Payload '.(empty($str) ? "[empty payload]" : $str).' could not be parsed!');
	exit;	
}

$str='';


$ordersArray=Order::getOrderDetailsByStatus($db,$pollingRequest->jobType, $request->count ?? 10);
$count=count($ordersArray);
Logger::info("Found $count order".($count!=1 ? "s" : "")." to upload to Switch!");
$str='';
foreach($ordersArray as $orderLine)
{
    $order=null;
    try
    {   $order = new Order($db, $orderLine);    }
    catch (Exception $e)
    {   Connector::dd($e);  }
    Logger::info("Appending JSON file for order {$order->getOrderId()} to response");
    if(strlen($str)>0)
    {	$str.=',';	}
    $str.=$order->getJSONPayload();
}
$str='['.$str.']';
$connector->sendPayload($str);
Logger::info("--------------------------- end Switch polling request ---------------------------");
exit;

