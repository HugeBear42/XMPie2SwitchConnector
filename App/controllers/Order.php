<?php
// Order.php	Simple class that encapsulates an Order.
// @frank.hubert@xmpie.comp v1.00 of 2024-12-09

namespace App\controllers;

use App\utils\ApplicationException;
use App\utils\Database;
use App\utils\Logger;

class Order
{
	const NEW = 'new';
	const PROCESSING = 'processing';
	const ERROR = 'error';
	const DELIVERING = 'delivering';
	const DELIVERED = 'delivered';
	const RETRY = 'retry';
	const STATUS_ARRAY = [ self::NEW, self::PROCESSING, self::ERROR, self::DELIVERING, self::DELIVERED, self::RETRY ];

    public static function getOrderIdFromArray(array $array) : int
    {
        return array_key_exists('@OrderId', $array['Order']) ? $array['Order']['@OrderId'] : $array['Order']['OrderId'];	// change from DisplayOrderId to OrderId check if the OrderId tag is preceded by a '@'
    }
    public static function getOrderProductIdsAsString(array $array) : string
    {
        $str="";
        foreach($array['Order']['OrderProducts']['OrderProduct'] as $orderProduct)
        {
            if(!empty($str))
            {	$str.=',';	}
            $str.=array_key_exists('@id', $orderProduct) ? $orderProduct['@id'] : $orderProduct['id'];
        }
        return $str;
    }
    /**
     * @throws ApplicationException
     */
    public static function createNewOrder(Database $db, array $array, string $xmlData) : Order
    {
        $orderId=self::getOrderIdFromArray($array);
        $orderProducts=self::getOrderProductIdsAsString($array);

        $query="INSERT INTO Orders (OrderId, OrderProductIds, CreationDateTime, ModificationDateTime, Status, TrackingId, Message, RetryCount, XMLOrderData, JSONOrderData) VALUES(:OrderId, :OrderProductIds, ".($db->getType()=='mysql' ? 'UTC_TIMESTAMP(), UTC_TIMESTAMP()' : 'GETUTCDATE(), GETUTCDATE()').", 'new', '', '', 0, :XMLOrderData, :JSONOrderData)";
        $params=[ 'OrderId'=>$orderId, 'OrderProductIds'=>$orderProducts, 'XMLOrderData'=>base64_encode(gzcompress($xmlData)), 'JSONOrderData'=>base64_encode(gzcompress(json_encode($array))) ];
        $db->query($query, $params);
        Logger::info("Database: Created Order $orderId, products are $orderProducts, status is 'new'");
        return new Order($db, self::getOrderDetails( $db, $orderId));
    }
	public static function checkOrderArray(array $array) : bool
	{
		return	!empty($array) &&
			array_key_exists('OrderId', $array) && is_numeric($array['OrderId']) &&
			array_key_exists('OrderProductIds', $array) && !empty($array['OrderProductIds'] ) &&
			array_key_exists('JSONOrderData', $array) && !empty($array['JSONOrderData']);	
	}

    /**
     * @throws ApplicationException
     */
    public function __construct(private readonly Database $db, private array $orderArray)
	{
		if(!self::checkOrderArray($this->orderArray))
		{	throw new ApplicationException('Invalid order array received: '.print_r($this->orderArray, true));	}
    }
	// $orderArray contains following data ['OrderId', 'OrderProductIds', 'CreationDateTime', 'ModificationDateTime', 'Status', 'TrackingId', 'Message', 'RetryCount', 'ActualDeliveryId', 'XMLOrderData', 'JSONOrderData']
	
	public function getJSONPayload() : string
	{	return gzuncompress(base64_decode($this->orderArray['JSONOrderData']));	}
	public function getOrderId() : int
	{	return (int)$this->orderArray['OrderId'];	}
	
	public function getOrderProductIdsAsArray() : array
	{   return explode (",", $this->orderArray['OrderProductIds']); }
    public function getActualDeliveryId() : int
    {   return $this->orderArray['ActualDeliveryId'];	}
    public function getTrackingId() : string
    {   return $this->orderArray['TrackingId'];	}

	public function updateStatus(string $status, string $message='') : void    // Update the DB, no status update back to uStore
	{
		if(in_array($status, self::STATUS_ARRAY))
        {
            $query="UPDATE Orders SET Status=:Status, Message=:Message, ModificationDateTime=".($this->db->getType()=='mysql' ? 'UTC_TIMESTAMP()' : 'GETUTCDATE()')." WHERE OrderId=:OrderId";
            $paramsArray=['Status'=>$status, 'Message'=>$message, 'OrderId'=>$this->getOrderId()];
            $this->db->query($query, $paramsArray);
            Logger::info("Database: Order {$this->getOrderId()}, status updated to '$status', message is '$message'");
        }

	}
	
	public function setRetryCount(int $retryCount) : void
	{
		$query="UPDATE Orders SET RetryCount=:RetryCount WHERE OrderId=:OrderId";
		$params=['RetryCount'=>$retryCount, 'OrderId'=>$this->getOrderId()];
		$this->db->query($query, $params);
		$this->orderArray['RetryCount']=$retryCount;
	}
	public function getRetryCount() : int
	{	return (int)$this->orderArray['RetryCount'];	}

    public function setActualDeliveryId(int $actualDeliveryId) : void
    {
        $query="UPDATE Orders SET ActualDeliveryId=:ActualDeliveryId WHERE OrderId=:OrderId";
        $params=['ActualDeliveryId'=>$actualDeliveryId, 'OrderId'=>$this->getOrderId()];
        $this->db->query($query, $params);
        $this->orderArray['ActualDeliveryId']=$actualDeliveryId;
        Logger::info("Database: Order {$this->getOrderId()}, actualDeliveryId updated to '$actualDeliveryId'");
    }

    public function setTrackingId(string $trackingId) : void
    {
        $query="UPDATE Orders SET TrackingId=:TrackingId WHERE OrderId=:OrderId";
        $params=['TrackingId'=>$trackingId, 'OrderId'=>$this->getOrderId()];
        $this->db->query($query, $params);
        $this->orderArray['TrackingId']=$trackingId;
        Logger::info("Database: Order {$this->getOrderId()}, trackingId updated to '$trackingId'");
    }

// A bunch of static helper functions.
	
	public static function getOrderDetails(Database $db, int $orderId) : array
	{
		$query="SELECT OrderId, OrderProductIds, CreationDateTime, ModificationDateTime, Status, TrackingId, Message, RetryCount, ActualDeliveryId, XMLOrderData, JSONOrderData FROM Orders WHERE OrderId=:OrderId";
		$params=['OrderId'=>$orderId];
		$result=$db->query($query, $params)->get();
		return empty($result) ? $result : $result[0];	// Return only the first element
	}
	public static function orderExists(Database $db, int $orderId) : bool
	{	return !empty(self::getOrderDetails($db, $orderId));	}
	
	public static function getOrderDetailsByStatus(Database $db, string $status, int $limit=10) : array
	{
		$limit = ($limit > 100 ) ? 100 : (max($limit, 1));
		$top=$db->getType()=='mysql' ? "" : "TOP $limit";
		$limit=$db->getType()=='mysql' ? "LIMIT $limit" : "";
		
		$query="SELECT $top OrderId, OrderProductIds, CreationDateTime, ModificationDateTime, Status, TrackingId, Message, RetryCount, ActualDeliveryId, XMLOrderData, JSONOrderData FROM Orders WHERE Status=:Status $limit";
		//Logger::info($query);
		$params=['Status'=>$status];
		return $db->query($query, $params)->get();
	}

    public static function deleteOrder(Database $db, int $orderId) : void
    {
        $query="DELETE FROM Orders WHERE OrderId=:OrderId";
        $params=['OrderId'=>$orderId];
        Logger::info("About to delete order $orderId, query is $query");
        $db->query($query, $params);
        Logger::info("Database: Order $orderId deleted!");
    }

    public static function checkOrderExists(Database $db, int $orderId) : bool
    {
        $query="SELECT COUNT(orderId) AS total FROM Orders WHERE OrderId=:OrderId";
        $params=['OrderId'=>$orderId];
        $arr=$db->query($query, $params)->find();
        return $arr['total']==1;
    }
    public static function cleanup(Database $db, int $days=30) : void
    {
        $days = ($days > 90 ) ? 90 : (max($days, 30));
        $query="delete from orders where CreationDateTime < dateadd(day, -$days, getDate())";
        if($db->getType()=='mysql')
            $query="delete from orders where CreationDateTime < now() - INTERVAL $days DAY";
        $db->query($query);
    }

}
		