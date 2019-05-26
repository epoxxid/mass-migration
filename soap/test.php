<?php

require '../lib/MMConfig.php';
require '../lib/Logger/MMStdoutLogger.php';
require '../lib/Logger/MMWriteToFileLogger.php';
require '../lib/Api/ApiClient.php';

// Load configuration
$config = new MMConfig('../config.ini');

// Init logger
//$logger = new MMWriteToFileLogger(dirname(__DIR__) . "/mass-migration.log");
$logger = new MMStdoutLogger();
$logger->setLevel($config->getLogLevel());

// Perform request
$client = new ApiClient($config, $logger);
