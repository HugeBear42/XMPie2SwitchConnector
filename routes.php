<?php

return
[
'/switch'=>__DIR__.'/views/index.php',
'/switch/uploadOrderXML'=>__DIR__.'/APP/controllers/uploadOrderXML.php',                   // called by uStore to upload the orderXML
'/switch/sendDataToSwitch'=>__DIR__.'/APP/controllers/sendDataToSwitch.php',               // push 'retry' data to Switch webhook
'/switch/pollDataFromSwitch'=>__DIR__.'/APP/controllers/getXMPieOrderData.php',            // Switch pulls the data from the connector
'/switch/updateXMPieOrderStatus'=>__DIR__.'/APP/controllers/updateXMPieOrderStatus.php',   // Push the order status to uStore
'/switch/switchSimulator'=> __DIR__ . '/public/test/switchSimulator.php'                   // a dummy endpoint that dumps header & contents to the screen!

];