<?php

		$array = ['action'=>'polling', 'jobType'=>'new', 'count'=>10];

		//$url = 'https://manchester.xmpie.net/switch/index.php';
        $url='http://localhost:8080/pollDataFromSwitch';
		$payload=json_encode($array);
		$headerArray=["Content-Type: application/json; charset=utf-8", "Authorization: Basic ".base64_encode("switch:switch")];
		$options = array('http' => array('method' => 'POST',  'header' => $headerArray, 'ignore_errors' => true, 'content'=>$payload));
		$context  = stream_context_create($options);
		$response = file_get_contents($url, false, $context);	// Send payload to back to XMPie  & check return value
		echo $response;