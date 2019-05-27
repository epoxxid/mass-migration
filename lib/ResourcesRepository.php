<?php

class ResourcesRepository
{
    const STATUS_FINISHED = 'finished';

    /**
     * @var array
     */
    private $dependencies = array();

    public function __construct()
    {
        $this->fetchDependenciesData();
    }

    /**
     * @return array
     */
    public function getIndependentItems()
    {
        $filtered = array();

        foreach ($this->dependencies as $dep) {
            // Item processed already
            if (!empty($dep['syncKey']) || $dep['status'] === self::STATUS_FINISHED) {
                continue;
            }

            // Item has no dependencies
            if (empty($dep['dependsOn'])) {
                continue;
            }

            // Check for resolved dependencies
            $isIndependent = true;
            foreach ($this->dependencies as $target) {
                if (in_array($target['id'], $dep['dependsOn'])) {
                    $isIndependent = $isIndependent && !empty($target['syncKey']);
                }
            }

            $filtered[] = $dep;
        }

        return $filtered;
    }

    /**
     * @param $itemId
     * @param $syncKey
     */
    public function updateDependencyItem($itemId, $syncKey)
    {
        foreach ($this->dependencies as &$dep) {
            if ($dep['id'] === $itemId) {
                $dep['syncKey'] = (string) $syncKey;
                $dep['status'] = self::STATUS_FINISHED;
            }
        }
    }

    private function fetchDependenciesData()
    {
        $this->dependencies = array(
            array(
                'id' => 1,
                'type' => 'folder',
                'title' => 'Root',
                'status' => null,
                'dependsOn' => null,
                'syncKey' => '',
                'courseSyncKey' => 'course-history',
                'userSyncKey' => 'user_content'
            ),
            array(
                'id' => 2,
                'type' => 'folder',
                'title' => 'My Documents',
                'status' => null,
                'dependsOn' => array(1),
                'syncKey' => '',
                'courseSyncKey' => 'course-history',
                'userSyncKey' => 'user_content'
            ),
            array(
                'id' => 3,
                'type' => 'folder',
                'title' => 'Cars',
                'status' => null,
                'dependsOn' => array(2),
                'syncKey' => '',
                'courseSyncKey' => 'course-history',
                'userSyncKey' => 'user_content'
            ),
        );
    }
}