<?php

require_once '../lib/OrgApiClient.php';
require_once '../lib/SoapClientDebugger.php';
require_once '../lib/OrgApiFolderCreator.php';

$folderCreator = new OrgApiFolderCreator(new OrgApiClient(), true);
$folderCreator->createFolder(null, array(
    'Title' => 'Parent folder',
    'Description' => 'Some folder description',
    'CourseSyncKey' => 'course-history',
    'UserSyncKey' => 'user_content',
));

