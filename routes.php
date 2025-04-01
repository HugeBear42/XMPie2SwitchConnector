<?php

$router->get('/switch',  'views/index.php');                                                    // Application home page
$router->post('/switch/uploadOrderXML', 'APP/controllers/uploadOrderXML.php');                  // endpoint used by uStore trigger to upload the orderXML
$router->post('/switch/sendDataToSwitch', 'APP/controllers/sendDataToSwitch.php');              // push 'retry' data to Switch webhook
$router->post('/switch/pollDataFromSwitch', 'APP/controllers/getXMPieOrderData.php');           // Switch pulls the data from the connector
$router->post('/switch/updateXMPieOrderStatus', 'APP/controllers/updateXMPieOrderStatus.php');  // Switch updates the XMPie order status
$router->post('/switch/switchSimulator', 'public/test/switchSimulator.php');                    // Used for testing purposes.
