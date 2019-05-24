<?php

require_once '../lib/OrgApiClient.php';
require_once '../lib/OrgApiFolderCreator.php';

$folderCreator = new OrgApiFolderCreator(new OrgApiClient(), true);
$folderCreator->createFolder(null, array(
    'Title' => 'Parent folder',
    'CourseSyncKey' => 'Fronter_123',
    'UserSyncKey' => '1',
    'SyncKey' => 'hello'
));

