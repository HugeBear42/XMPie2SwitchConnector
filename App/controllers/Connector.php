<?php
// Order.php	Simple class that encapsulates an Order.
// @frank.hubert@xmpie.comp v1.00 of 2024-12-09

namespace App\controllers;

use App\utils\Database;
use App\utils\Logger;
use App\utils\WS_XMP_ActualDelivery;
use Exception;

use App\utils\WS_XMP_Production;
use JetBrains\PhpStorm\NoReturn;

readonly class Connector
{

    public function __construct(private Database $db, private array $configArray)
	{}

    public function registerNewOrderXMLFile(string $file) : ?Order
    {
        $order=null;
        $xmlContents=file_get_contents( $file );	// Use the debug file as a default value
        if($xmlContents===false)
        {
            Logger::error("Failed to load file $file!");
            return null;
        }
        try {
            $xmldata = simplexml_load_string($xmlContents);
            $converter = new OrderXML2JSON();
            $arrayData = $converter->convert($xmldata, $this->configArray['uStore'], true);    // Return an array representation of the order so we can extract the data to persist in the DB!

            $orderId = Order::getOrderIdFromArray($arrayData);
            Logger::info("New order id $orderId");
            if( Order::orderExists($this->db, $orderId) ) // Should never happen except when testing the application!
            {   Order::DeleteOrder($this->db, $orderId);   }
            $order=Order::createNewOrder($this->db, $arrayData, $xmlContents);
        } catch (Exception $ex) {
            Logger::error("Failed to process OrderXML file $file, error was: " . $ex);
        }
        return $order;
    }

    public function sendToProduction(Order $order) : void
    {
        try {
            $productionWS = new WS_XMP_Production($this->configArray['uStore']);
            foreach ($order->getOrderProductIdsAsArray() as $orderProductId)    // Send the artwork to production so it can be downloaded by switch.
            {
                $productionWS->sendToProduction($orderProductId);
                Logger::fine("Sent orderProductId $orderProductId to production!");
            }
        } catch (Exception $ex) {
            Logger::error("Failed to send order product to production, error was: " . $ex);
        }
    }

    public function processDelivering(array $uStoreConfigArray, Order $order) :int
    {
        $actualDeliveryId = $order->getActualDeliveryId();
        if( $actualDeliveryId != -1 )	// We can only call this method once, if it has been set, return existing value.
        {   Logger::warning("ActualDeliveryId was already set, value: $actualDeliveryId");  }
        else
        {
            $orderProductArray=$order->getOrderProductIdsAsArray();
            if( !empty($orderProductArray) )
            {
                $deliveryWS=new WS_XMP_ActualDelivery($uStoreConfigArray);
                try
                {
                    $actualDeliveryId=$deliveryWS->createDeliveryByOrderProducts($orderProductArray, date('Y-m-d\TH:i:s'), $order->getTrackingId() );
                    $order->setActualDeliveryId($actualDeliveryId);
                    Logger::info("Actual Delivery created for order {$order->getOrderId()}, Id: $actualDeliveryId");
                }
                catch(Exception $ex)
                {   Logger::error("Failed to process delivery for order {$order->getOrderId()}, exception was: ".print_r($ex->getMessage(), true));  }
            }
            else
            {	Logger::error("Order {$order->getOrderId()} doesn't contain any order products ?!");	}
        }
        return $actualDeliveryId;
    }

    public function processDelivered(array $uStoreConfigArray, Order $order) :void
    {
        $actualDeliveryId = $order->getActualDeliveryId();
        if( $actualDeliveryId == -1 )	// We first need to call processDelivering() & get an $actualDeliveryId value!.
        {	$actualDeliveryId = $this->processDelivering($uStoreConfigArray, $order);	}
        if( $actualDeliveryId != -1)
        {
            $deliveryWS=new WS_XMP_ActualDelivery($uStoreConfigArray);
            try
            {
                $deliveryWS->manualDeliveryArrived($actualDeliveryId);
                Logger::info("Delivery $actualDeliveryId for order {$order->getOrderId()} set to delivered");
            }
            catch(Exception $ex)
            {	Logger::error("Failed to set delivery $actualDeliveryId for order {$order->getOrderId()} to delivered, exception was: ".print_r($ex->getMessage(), true));	}
        }
    }
    public function checkAuthentication() : bool    //  if basic authentication is enabled, check credentials
    {
        $authenticated=true;
        if(array_key_exists('authentication', $this->configArray) && array_key_exists('type', $this->configArray['authentication']) && $this->configArray['authentication']['type'] == 'basic')
        {
            $authenticated= isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] == $this->configArray['authentication']['user'] && isset($_SERVER['PHP_AUTH_PW']) &&  $_SERVER['PHP_AUTH_PW'] == $this->configArray['authentication']['pass'];
        }
        return $authenticated;
    }
    #[NoReturn] static function dd(Exception $ex) : void
    {
        http_response_code(500);
        Logger::error($ex->getMessage());
        exit;
    }
    public function printErrorMessage(string $message, $code=400) : void
    {
        http_response_code($code);
        header("Content-Type: application/json ; charset=utf-8");
        Logger::error($message);
        echo json_encode(["status"=>"error" , "message"=>$message ]);
    }
    public function sendPayload(string $payload) : void
    {
        http_response_code(200);
        header("Content-Type: application/json ; charset=utf-8");
        echo $payload;
    }
    public function sendJSONToSwitch(Order $order): string
    {
        $url=$this->configArray['switch']['url'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);

        curl_setopt($ch, CURLOPT_URL, $url);                                // Set the url
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");              // Send request data using POST method
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type:application/json; charset=utf-8']);    // Data content-type is sent as JSON
        curl_setopt($ch, CURLOPT_POST, true);                         // POST request
        curl_setopt($ch, CURLOPT_POSTFIELDS, $order->getJSONPayload());     // Curl POST the JSON data to send the request
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);                        // Set a timeout for the cURL request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);               // return the response from the server as a string instead of outputting it directly
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);              // avoid following redirects, if any
        if(array_key_exists('authentication', $this->configArray) && array_key_exists('type', $this->configArray['authentication']) && $this->configArray['authentication']['type'] == 'basic')
        {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->configArray['authentication']['user'].":".$this->configArray['authentication']['pass']);
            Logger::info("Basic authentication enabled!");
        }
        $response = curl_exec($ch);                                                // Execute the cURL request and capture the response
        Logger::info("Switch server response: $response");
        if (curl_errno($ch))        // An error was found, check if it is recoverable
        {
            Logger::error("Curl generated an error,  code: ".curl_errno($ch).", ". curl_error($ch));
            return ( curl_errno($ch)==CURLE_URL_MALFORMAT ||
                curl_errno($ch)==CURLE_COULDNT_RESOLVE_PROXY ||
                curl_errno($ch)==CURLE_COULDNT_RESOLVE_HOST ||
                curl_errno($ch)==CURLE_COULDNT_CONNECT )  ?  Order::RETRY : Order::ERROR;
        }

        $decodedResponse = json_decode($response, true);    // Decode JSON response if it is a JSON string
        if ($decodedResponse == null)                                 // Check if decoding was successful
        {   return Order::ERROR;    }
        if( is_array($decodedResponse) && array_key_exists('status', $decodedResponse) )
        {
            $status = trim(strtolower($decodedResponse['status']));
            return $status!=Order::ERROR ? Order::PROCESSING : Order::ERROR;
        }
        return Order::ERROR;
    }

}
		