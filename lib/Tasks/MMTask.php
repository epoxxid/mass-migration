<?php

/**
 * Base Mass Migration task to extend from
 */
abstract class MMTask
{
    /** @var string User sync key string */
    protected $userSyncKey;
    /** @var string Course sync key string */
    protected $courseSyncKey;

    /**
     * @param array $taskData
     */
    public function __construct(array $taskData)
    {
        if (isset($taskData['userSyncKey'])) {
            $this->userSyncKey = (string) $taskData['userSyncKey'];
        }

        if (isset($taskData['courseSyncKey'])) {
            $this->courseSyncKey = (string) $taskData['courseSyncKey'];
        }
    }

    /**
     * @return array
     */
    public function toRequestFormat()
    {
        return array(
            'UserSyncKey' => $this->userSyncKey,
            'CourseSyncKey' => $this->courseSyncKey
        );
    }
}
