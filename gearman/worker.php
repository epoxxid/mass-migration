<?php

require_once '../lib/MassMigrationService.php';
require_once '../lib/ApiFolderCreator.php
$workerService = new GearmanWorkerService(array(
    MassMigrationService::TASK_CREATE_FOLDER => 'createFolder',
    MassMigrationService::TASK_CHECK_STATUS => 'checkStatus'
));


/**
 * @param GearmanJob $job
 * @return string
 */
function createFolder($job)
{
    $folder = unserialize($job->workload());

    $id = $folder['id'];
    $title = $folder['title'];

    echo "\nNEW TASK: create folder '{$title}' with ID = {$id}\n";


    echo ">>> API REQUEST: createFolder($title)\n";
    sleep(1);
    $qId = mt_rand(1, 100); // Imitate receiving queue item ID
    echo "<<< API RESPONSE: message enqueued ID = {$qId}\n";

    return serialize(array(
        'event' => MassMigrationService::EVENT_ITEM_ENQUEUED,
        'itemId' => $id,
        'itemQueueId' => $qId
    ));
}

/**
 * @param GearmanJob $job
 * @return string
 */
function checkStatus($job)
{
    list($id, $qId) = unserialize($job->workload());
    echo "\nNEW TASK: get status of item with ID = $id" . PHP_EOL;

    echo ">>> API REQUEST: getMessageResult($id)\n";
    sleep(1);
    $syncKey = md5(time() . microtime());
    echo "<<< API RESPONSE: folder created with syncKey = {$syncKey}\n";

    return serialize(array(
        'event' => MassMigrationService::EVENT_FOLDER_CREATED,
        'itemId' => $id,
        'syncKey' => $syncKey
    ));
}
