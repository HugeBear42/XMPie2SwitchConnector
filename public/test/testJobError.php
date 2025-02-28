<?php
header('HTTP/1.1 400 Bad request received!');
header('Content-Type: application/json; charset=utf-8');
echo '{	"orderId" : 43235 ,"status" : "error", "message": "Testing the bad request scenario!"}';