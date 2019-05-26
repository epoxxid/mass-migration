<?php

require '../lib/MMConfig.php';
require_once '../lib/Api/ApiClient.php';
require_once '../lib/Api/ApiFolderCreator.php';
require_once '../lib/Logger/MMStdoutLogger.php';

// Load configuration
$config = new MMConfig('../config.ini');

// Init logger
$logger = new MMStdoutLogger();
$logger->setLevel($config->getLogLevel());

// Init service
$folderCreator = new ApiFolderCreator($config, $logger);

// Create folder
$folderCreator->createFolder(null, array(
    'Title' => 'Parent folder',
    'Description' => 'Some folder description',
    'CourseSyncKey' => 'course-history',
    'UserSyncKey' => 'user_content',
));

