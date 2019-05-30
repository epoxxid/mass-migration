<?php

require '../lib/Logger/MMStdoutLogger.php';
require '../lib/Api/ApiFileUploader.php';
require '../lib/MMConfig.php';

// Load configuration
$config = new MMConfig('../config.ini');

// Init logger
$logger = new MMStdoutLogger();
$logger->setLevel('debug');

// Perform request
$uploader = new ApiFileUploader($config, $logger);
$uploader->uploadFile('/path/to/some/file');
