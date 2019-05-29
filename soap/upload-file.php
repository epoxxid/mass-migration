<?php

require '../lib/MMConfig.php';
//require '../lib/Api/ApiClient.php';
require '../lib/Api/ApiFileStream.php';
require '../lib/Logger/MMStdoutLogger.php';

// Load configuration
$config = new MMConfig('../config.ini');

// Init logger
$logger = new MMStdoutLogger();
$logger->setLevel($config->getLogLevel());

// Init service
$fileUploader = new ApiFileStream($config, $logger);

$result = $fileUploader->uploadFile();
