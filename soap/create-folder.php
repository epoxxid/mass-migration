<?php

require '../lib/MMConfig.php';
require '../lib/Api/ApiClient.php';
require '../lib/Api/ApiFolderCreator.php';
require '../lib/Logger/MMStdoutLogger.php';

// Load configuration
$config = new MMConfig('../config.ini');

// Init logger
$logger = new MMStdoutLogger();
$logger->setLevel($config->getLogLevel());

// Init service
$folderCreator = new ApiFolderCreator($config, $logger);

// Create folder
$result = $folderCreator->createFolder(null, array(
    'Title' => 'Some folder',
    'Description' => 'Some folder description',
    'CourseSyncKey' => 'course-history',
    'UserSyncKey' => 'user_content',
));
