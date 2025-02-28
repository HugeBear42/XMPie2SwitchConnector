<?php

return
[
	'debug'=>true,
	'db'=>
	[
		'type'=> 'mysql',
		'connection' =>
		[
			'host' => 'localhost',
			'port' => '3306',
			'dbname' => 'SwitchConnector',
			'charset' => 'utf8mb4'
		],
		'user' => 'SwitchUser',
		'pass' => 'xxxx'
	],
    'authentication'=>
        [
    "type"=> 'none',
    "user"=>"switch",
    "pass"=>"switch"
    ],
	'switch'=>
	[
		"dataTransfer"=>"switchwebhook",
        "url" => 'http://localhost:8080/switchSimulator',
		"allowStatusFeedback" => true,
		 "signatureKey" =>""
	],
	'uStore' =>
	[
		"apiUser" => "api@ustore.xmpie.net",
		"apiPass"=>"xxxx",
		"baseURL" => "https://manchester.xmpie.net/"
	]
];