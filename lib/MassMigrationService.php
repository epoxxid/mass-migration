<?php

require_once 'ResourcesRepository.php';

class MassMigrationService
{
    const EVENT_ITEM_ENQUEUED = 'item-enqueued';
    const EVENT_FOLDER_CREATED = 'folder-created';

    const TASK_CREATE_FOLDER = 'create-folder';
    const TASK_CHECK_STATUS = 'check-status';

    /** @var GearmanClient */
    private $gearmanClient;

    /** @var ResourcesRepository */
    private $resourcesRepository;

    public function __construct()
    {
        $this->resourcesRepository = new ResourcesRepository();

        $this->gearmanClient = new GearmanClient();
        $this->gearmanClient->addServer();
        $this->gearmanClient->setCompleteCallback(array($this, 'onTaskCompleted'));
    }

    public function start()
    {
        // Check dependency-free items and add them into work
        $this->handleResolvedDependencies();

        if (!$this->gearmanClient->runTasks()) {
            $this->log("Error occurred: {$this->gearmanClient->error()}");
            exit;
        }

        $this->log('Finished!');
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
        $this->log("Folder created with syncKey = {$syncKey}");
        // Update database record
        $this->resourcesRepository->updateDependencyItem($itemId, $syncKey);
        // Check for resolved dependencies
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

    private function handleResolvedDependencies()
    {
        $this->log(PHP_EOL . 'Checking if there any dependencies are resolved and item can be processed');

        $deps = $this->resourcesRepository->getIndependentItems();
        foreach ($deps as $dep) {
            $this->processDependency($dep);
        }
    }

    private function processDependency(array $dependency)
    {
        $taskData = serialize($dependency);
        switch ($dependency['type']) {
            case 'folder':
                $this->gearmanClient->addTask(self::TASK_CREATE_FOLDER, $taskData);
                break;
        }
    }
}
