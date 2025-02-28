-- 2024-12-09	
-- The script for Microsoft SQL tables for the uStore <-> Switch connector


USE SwitchConnector;

CREATE TABLE Orders (
	OrderId INT NOT NULL,
	OrderProductIds VARCHAR(1024) NOT NULL,
	CreationDateTime DATETIME NOT NULL,
	ModificationDateTime DATETIME NOT NULL,
	Status VARCHAR(10) NOT NULL CHECK (Status IN('new', 'processing', 'error', 'delivering', 'delivered', 'retry')),
	TrackingId VARCHAR(1024) NOT NULL DEFAULT '',
	Message VARCHAR(1024) NOT NULL DEFAULT '',
	JSONFilePath VARCHAR(1024) NOT NULL DEFAULT '',
	RetryCount INT DEFAULT 0,
	ActualDeliveryId INT DEFAULT -1,
	XMLOrderData VARCHAR(8000),
	JSONOrderData VARCHAR(8000),	
	PRIMARY KEY (OrderId)
);



 