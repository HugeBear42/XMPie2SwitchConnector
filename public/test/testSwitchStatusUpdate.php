<?php


$orderId=43235;
$status='delivering';
$trackingId='track_12345';
$message="A message for order $orderId";
if(isset($_REQUEST['orderId']) && is_numeric($_REQUEST['orderId']))
{	$orderId=$_REQUEST['orderId'];	}
if( isset($_REQUEST['status']) )
{	$status=strtolower($_REQUEST['status']);	}
if( isset($_REQUEST['trackingId']) )
{	$trackingId=$_REQUEST['trackingId'];	}
if( isset($_REQUEST['message']) )
{	$message=$_REQUEST['message'];	}

		//$array = [['orderId'=>43234, 'action'=>'statusUpdate', 'status'=>'delivering', 'message'=>'A first message here for order 43234', 'trackingId'=>'https://manchester.xmpie.net/tracking/MyTrakingNumber_123'],
		//		  ['orderId'=>43235, 'action'=>'statusUpdate', 'status'=>'delivering', 'message'=>'Another message here for order 43235', 'trackingId'=>'https://manchester.xmpie.net/tracking/MyTrakingNumber_999']];
		$array = [['orderId'=>$orderId, 'action'=>'statusUpdate', 'status'=>$status, 'message'=>$message, 'trackingId'=>$trackingId]];
	
	//	$url = 'https://manchester.xmpie.net/switch/index.php';
        $url='http://localhost:8080/updateXMPieOrderStatus';
		$payload=json_encode($array);
		$headerArray=["Content-Type: application/json; charset=utf-8", "Authorization: Basic ".base64_encode("switch:switch")];
		$options = array('http' => array('method' => 'POST', 'ignore_errors' => true,  'header' => $headerArray, 'content'=>$payload));
		$context  = stream_context_create($options);
		$response = file_get_contents($url, false, $context);	// Send payload to back to XMPie  & check return value
		echo $response;