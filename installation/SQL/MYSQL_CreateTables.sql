-- 2024-12-09	
-- The script for MYSQL tables for the uStore <-> Switch connector


USE SwitchConnector;

CREATE TABLE Orders (
	OrderId INT NOT NULL,
	OrderProductIds VARCHAR(1024) NOT NULL,
	CreationDateTime DATETIME NOT NULL,
	ModificationDateTime DATETIME NOT NULL,
	Status ENUM('new','processing','error','printing','delivering','delivered','retry') DEFAULT 'new',
	TrackingId VARCHAR(1024) NOT NULL DEFAULT '',
	Message VARCHAR(1024) NOT NULL DEFAULT '',
	JSONFilePath VARCHAR(1024) NOT NULL DEFAULT '',
	RetryCount INT DEFAULT 0,
	ActualDeliveryId INT DEFAULT -1,
	XMLOrderData TEXT,
	JSONOrderData TEXT,
	PRIMARY KEY (OrderId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


 