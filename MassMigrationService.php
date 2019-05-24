<?php


class MassMigrationService
{
    const EVENT_ITEM_ENQUEUED = 'item-enqueued';
    const EVENT_FOLDER_CREATED = 'folder-created';

    const TAKS_CREATE_FOLDER = 'create-folder';
    const TASK_CHECK_STATUS = 'check-status';

    const STATUS_FINISHED = 'finished';

    /**
     * @var GearmanClient
     */
    private $gearmanClient;

    /**
     * @var array
     */
    private $dependencies = array();

    public function __construct()
    {
        $this->gearmanClient = new GearmanClient();
        $this->gearmanClient->addServer();
        $this->gearmanClient->setCompleteCallback(array($this, 'onTaskCompleted'));
    }

    public function start()
    {
        // Fetch room data and build list of dependencies
        $this->initDependencies();
        // Check dependency-free items and add them into work
        $this->handleResolvedDependencies();

        if (!$this->gearmanClient->runTasks()) {
            $this->log("Error occurred: {$this->gearmanClient->error()}");
            exit;
        }

        $this->log('Finished!');
    }



    private function initDependencies()
    {
        $this->dependencies = array(
            array(
                'id' => 1,
                'type' => 'folder',
                'name' => 'Root',
                'status' => null,
                'dependsOn' => null,
                'syncKey' => ''
            ),
            array(
                'id' => 2,
                'type' => 'folder',
                'name' => 'My Documents',
                'status' => null,
                'dependsOn' => array(1),
                'syncKey' => ''
            ),
            array(
                'id' => 3,
                'type' => 'folder',
                'name' => 'Cars',
                'status' => null,
                'dependsOn' => array(2),
                'syncKey' => ''
            ),
        );
    }

    /**
     * @param GearmanTask $task
     */
    public function onTaskCompleted($task)
    {
        $data = unserialize($task->data());
        switch ($data['event']) {
            case self::EVENT_ITEM_ENQUEUED:
                $this->onItemEnqueued($data['itemId'], $data['itemQueueId']);
                break;
            case self::EVENT_FOLDER_CREATED:
                $this->onFolderCreated($data['itemId'], $data['syncKey']);
                break;
        }
    }

    /**
     * @param $itemId
     * @param $syncKey
     */
    private function onFolderCreated($itemId, $syncKey)
    {
        $this->log( "Folder created with syncKey = {$syncKey}");

        // Update database record
        $this->updateDependencyItem($itemId, $syncKey);
        $this->handleResolvedDependencies();
    }


    /**
     * @param $itemId
     * @param $itemQueueId
     */
    private function onItemEnqueued($itemId, $itemQueueId)
    {
        // Take pause to void possibility of overloading the Gearman
        sleep(1);

        $taskData = serialize(array($itemId, $itemQueueId));
        $this->gearmanClient->addTask(self::TASK_CHECK_STATUS, $taskData);
        $this->log("Added task to check status of item with ID = $itemId");
    }

    /**
     * @param $str
     */
    private function log($str)
    {
        echo "$str\n";
    }

    /**
     * @param $itemId
     * @param $syncKey
     */
    private function updateDependencyItem($itemId, $syncKey)
    {
        foreach ($this->dependencies as &$dep) {
            if ($dep['id'] === $itemId) {
                $dep['syncKey'] = $syncKey;
                $dep['status'] = self::STATUS_FINISHED;
                /** @noinspection SqlResolve */
                /** @noinspection SqlNoDataSourceInspection */
                $this->log("UPDATE deps SET syncKey = $syncKey AND status = 'finished' WHERE id = $itemId");
            }
        }
    }

    private function handleResolvedDependencies()
    {
        $this->log(PHP_EOL . 'Checking if there any dependencies are resolved and item can be processed');
        foreach ($this->dependencies as $dep) {
            // Item processed already
            if (!empty($dep['syncKey']) || $dep['status'] === self::STATUS_FINISHED) {
                continue;
            }

            // Item has no dependencies
            if (empty($dep['dependsOn'])) {
                $this->log('Item with ID ' . $dep['id'] . ' has no dependencies at all');
                $this->triggerFolderCreation($dep);
                continue;
            }

            $hasNoDeps = true;
            foreach ($this->dependencies as $target) {
                if (in_array($target['id'], $dep['dependsOn'])) {
                    $hasNoDeps = $hasNoDeps && !empty($target['syncKey']);
                }
            }

            if ($hasNoDeps) {
                $this->log('Resolved dependencies of item with ID = ' . $dep['id']);
                $this->triggerFolderCreation($dep);
            }
        }
    }

    /**
     * @param array $dep
     */
    private function triggerFolderCreation($dep)
    {
        $folderId = $dep['id'];
        $folderName = $dep['name'];
        // Here we assume we send parentID for item etc.
        $taskData = serialize(array($folderId, $folderName));
        $this->gearmanClient->addTask(self::TAKS_CREATE_FOLDER, $taskData);

        $this->log("Added task for creation of the folder '{$folderName}' with ID = {$folderId}");
    }
}
