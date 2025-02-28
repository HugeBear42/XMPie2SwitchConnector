<?php
// A test endpoint that echoes a successful job response

use App\utils\Logger;
spl_autoload_register(function ($class) {$path= __DIR__ . '/../../../XMPie2SwitchConnector/' .str_replace('\\', '/', $class).'.php'; require $path;});	// Setup a simple PHP autoloader

Logger::info(print_r(getallheaders(), true));
Logger::info(print_r($_SERVER, true));

header('Content-Type: application/json ; charset=utf-8');

$payload=file_get_contents("php://input");
$array=json_decode($payload,true);
$response=['orderId'=>-1, 'status'=>'ok', 'message'=>'job submitted to workflow'];
if(is_array($array) && array_key_exists('Order',$array) && array_key_exists('OrderId',$array['Order']))
{
    http_response_code(200);
    $orderId=$array['Order']['OrderId'];
    $response['orderId']=$orderId;
}
else
{
    $response['status']='error';
    $response['message']='Invalid request parameters: '.$payload;
    http_response_code(400);
}
$response=json_encode($response);
echo $response;
exit;