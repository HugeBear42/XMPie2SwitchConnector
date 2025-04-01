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
		'pass' => 'xxxxx'
	],
    'authentication'=>[
        "type"=> 'basic',
        "user"=>"switch",
        "pass"=>"switch"
    ],
	'switch'=>
	[
		"dataTransfer"=>"switchwebhook",
        "url" => 'http://localhost:8080/switch/switchSimulator',
		"allowStatusFeedback" => true,
        "retentionDays" => 30
	],
	'uStore' =>
	[
		"apiUser" => "api@ustore.xmpie.net",
		"apiPass"=>"xxxxx",
		"baseURL" => "http://manchester.xmpie.net/"
	]
];