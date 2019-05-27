<?php

require 'MMServiceWorker.php';
require 'ResourcesRepository.php';

/**
 * Gearman client for Mass Migration service
 */
class MMServiceClient
{
    /** @var string Item added to queue */
    const EVENT_ITEM_ENQUEUED = 'item-enqueued';
    /** @var string Folder created */
    const EVENT_FOLDER_CREATED = 'folder-created';
    /** @var string Task processing failed */
    const EVENT_TASK_PROCESSING_FAILED = 'processing-failed';

    /** @var GearmanClient */
    private $gearmanClient;
    /** @var ResourcesRepository */
    private $resourcesRepository;
    /** @var MMLogger */
    private $logger;

    /**
     * @param MMLogger $logger
     */
    public function __construct(MMLogger $logger)
    {
        $this->logger = $logger;
        $this->resourcesRepository = new ResourcesRepository();

        $this->gearmanClient = new GearmanClient();
        $this->gearmanClient->addServer();
        $this->gearmanClient->setCompleteCallback(array($this, 'onTaskCompleted'));
    }

    /**
     * TODO: This method should be inner used by public methods such as exportRoom(), exportClient() etc.
     */
    public function start()
    {
        $this->logger->dbg('Start mass migration service', __METHOD__);

        // Check dependency-free items and add them into work
        $this->handleResolvedDependencies();

        if (!$this->gearmanClient->runTasks()) {
            $this->log("Error occurred: {$this->gearmanClient->error()}");
            exit;
        }

        $this->logger->dbg('Finished!', __METHOD__);
    }

    /**
     * @param GearmanTask $task
     */
    public function onTaskCompleted($task)
    {
        $data = unserialize($task->data());
        $this->logger->dbg('Finished processing of task', __METHOD__);
        switch ($data['event']) {
            case self::EVENT_ITEM_ENQUEUED:
                $this->onItemEnqueued($data['itemId'], $data['itemQueueId']);
                break;
            case self::EVENT_FOLDER_CREATED:
                $this->onFolderCreated($data['itemId'], $data['syncKey']);
                break;
            case self::EVENT_TASK_PROCESSING_FAILED:
                $this->onTaskProcessingFailed($data['itemId'], $data['message']);
                break;
        }
    }

    /**
     * @param $itemId
     * @param $syncKey
     */
    private function onFolderCreated($itemId, $syncKey)
    {
        $this->logger->info("Folder created with syncKey = {$syncKey}");
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
        $this->gearmanClient->addTask(MMServiceWorker::TASK_CHECK_STATUS, $taskData);
        $this->logger->dbg("Added task to check status of item with ID = $itemId");
    }

    /**
     * @param int $itemId
     */
    private function onTaskProcessingFailed($itemId, $message)
    {
        $this->logger->error($message, __METHOD__);
    }

    /**
     *
     */
    private function handleResolvedDependencies()
    {
        $this->logger->dbg('Checking if there any independent resource items to be processed', __METHOD__);

        $deps = $this->resourcesRepository->getIndependentItems();

        if ($numDeps = count($deps)) {
            $this->logger->dbg("$numDeps resource items are independent and will be processed", __METHOD__);
        } else {
            $this->logger->dbg('No new items to be processed', __METHOD__);
        }

        foreach ($deps as $dep) {
            $this->processResourceItem($dep);
        }
    }

    /**
     * @param array $item
     */
    private function processResourceItem(array $item)
    {
        $taskData = serialize($item);
        switch ($item['type']) {
            case 'folder':
                $this->logger->dbg("Adding task to Gearman for creating folder with name {$item['title']}");
                $this->gearmanClient->addTask(MMServiceWorker::TASK_CREATE_FOLDER, $taskData);
                break;
        }
    }
}
