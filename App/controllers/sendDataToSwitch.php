<?php
/*
	sendDataToSwitch.php © 2024-2025 frank@xmpie.com
	A script that will upload all orders in 'retry' status to Switch
	
	v1.00 of 2024-11-30	Genesis

*/

namespace App\controllers;

use App\utils\Database;
use App\utils\Logger;
use Exception;

// Setup a simple PHP autoloader
spl_autoload_register(function ($class) {$path=__DIR__.'/../../'.str_replace('\\', '/', $class).'.php'; require $path;});

$configArray=require __DIR__.'/../config/appConfig.php';
$db=null;
try {   $db = new Database($configArray['db']);    }
catch(Exception $ex)
{   Connector::dd($ex);  }

$connector = new Connector($db, $configArray);

$candidates=Order::getOrderDetailsByStatus($db, Order::RETRY);
if( !empty($candidates))
{
	Logger::info("------------------------------ Processing retry orders, ".sizeof($candidates)." orders found! ------------------------------");
	foreach($candidates as $line)
	{
        try
        {   $order = new Order($db, $line); }
        catch(Exception $ex)
        {   Connector::dd($ex);  }
        $status=$connector->sendJSONToSwitch($order);
        $order->updateStatus($status);
	}
    Logger::info("------------------------------ Retry orders processed! ------------------------------");
}
exit;
