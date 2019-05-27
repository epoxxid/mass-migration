<?php

/**
 * Mass Migration task to create folder
 */
class CreateFolderTask extends MMTask
{
    /** @var string */
    private $title;
    /** @var string */
    private $description;
    /** @var string|null */
    private $parentSyncKey;

    /**
     * @param $taskData
     */
    public function __construct($taskData)
    {
        parent::__construct($taskData);

        // Validate title
        if (!empty($taskData['title'])) {
            $this->title = (string) $taskData['title'];
        } else {
            throw new TaskInvalidDataException('Title is required for folder');
        }

        // Validate description
        if (!empty($taskData['description'])) {
            $this->description = (string) $taskData['description'];
        }

        // TODO: Validate sync key
        if (isset($taskData['parentSyncKey'])) {
            $this->parentSyncKey = $taskData['parentSyncKey'];
        }
    }

    /**
     * @return string|null
     */
    public function getParentSyncKey()
    {
        return $this->parentSyncKey ?: null;
    }

    /**
     * @return array
     */
    public function toRequestFormat()
    {
        return array_merge(parent::toRequestFormat(), array(
            'Title' => $this->title,
            'Description' => $this->description,
        ));
    }
}
