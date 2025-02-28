<?php
/*
	endpoint.php © 2024 frank@xmpie.com 
	The endpoint used by Enfocus Switch to update order status

    An order status update request body is formatted as follows: { "action" : "statusUpdate", "orderId" : 43235,  "status" : "delivering", "message" : "xxx",  "trackingId" : "xxxYYYzzz"}
	Multiple order status updates can be submitted as an array
	
	v1.00 of 2024-11-27	Genesis
    The request must be JSON-encoded as follows: {      }
	
*/

use App\utils\Database;
use App\utils\Logger;
use App\controllers\Order;
use App\controllers\Connector;

spl_autoload_register(function ($class) {$path= __DIR__ . '/../../' .str_replace('\\', '/', $class).'.php'; require $path;});	// Setup a simple PHP autoloader


function validateStatusUpdateRequest(object $obj) : bool
{	return property_exists( $obj, 'action') && $obj->action==='statusUpdate' && property_exists( $obj, 'orderId') && property_exists($obj, 'status') && in_array($obj->status, Order::STATUS_ARRAY);	}

$configArray= require __DIR__ . '/../config/appConfig.php';
$debug=$configArray['debug'];	// If true, script can be run from the command-line & will retrieve XML file from the tmp folder
Logger::setDebug($debug);
Logger::fine(print_r(getallheaders(), true));
Logger::fine(print_r($_SERVER, true));
$db=null;
try
{   $db=new Database($configArray['db']);   }
catch (Exception $e)
{   Connector::dd($e);  }
$connector=new Connector($db, $configArray);
if( !$connector->checkAuthentication() )
{
    $connector->printErrorMessage("Authentication failed", 401);
    exit;
}

$str=file_get_contents("php://input");	// get the JSON contents
Logger::info("--------------------------- start Switch statusUpdate request ---------------------------");
Logger::fine("Received payload from Switch: ".(empty($str) ? "[empty payload]" : $str));
$dataArray=null;
if(strlen($str)>0)
{
	$dataArray=json_decode($str, false);	// Returns an object or an array or null!
}
if(is_object($dataArray) )	// json is a single object, encapsulate in an array for normalised processing
{	$dataArray=[$dataArray];	}
else if(!is_array($dataArray))
{
	$connector->printErrorMessage('Payload '.(empty($str) ? "[empty payload]" : $str).' could not be parsed!');
	exit;	
}


$str='';
foreach($dataArray as $statusUpdateRequest)
{
    if( validateStatusUpdateRequest($statusUpdateRequest))
    {
        $orderId = $statusUpdateRequest->orderId;
		$order=null;
        try
        {   $order = new Order($db, Order::getOrderDetails( $db, $orderId));    }
        catch (Exception $e)
        {   Connector::dd($e);  }
		$status=strtolower( $statusUpdateRequest->status );	// ignore upper / lowercase in status string.
		$trackingId=$statusUpdateRequest->trackingId ?? '';
		$message=$statusUpdateRequest->message ?? '';

		$order->updateStatus($status, $message);
        if($trackingId!=='')
            $order->setTrackingId($trackingId);
        if($status==Order::DELIVERING )
        {
            $connector = new Connector( $db, $configArray);
            $connector->processDelivering($configArray['uStore'], $order);
        }
        else if($status==Order::DELIVERED)
        {
            $connector = new Connector( $db, $configArray);
            $connector->processDelivered($configArray['uStore'], $order);
        }
		if(strlen($str)>0)
		{	$str.=',';	}
		$str.='{"orderId" : '.$orderId.' , "status" : "ok" , "message" : "Order status updated to '.$status.'"}';
    }
    else
        $connector->printErrorMessage("Invalid JSON request found: ".json_encode($statusUpdateRequest).", the request will be ignored");
}
if(count($dataArray)>1)
{	$str='['.$str.']';	}
$connector->sendPayload($str);
Logger::info("--------------------------- end Switch statusUpdate request ---------------------------");
exit;

