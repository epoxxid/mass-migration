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

    public function getIndependentItems()
    {
        return array_filter($this->dependencies, function($dep) {
            // Item processed already
            if (!empty($dep['syncKey']) || $dep['status'] === self::STATUS_FINISHED) {
                return false;
            }

            // Item has no dependencies
            if (empty($dep['dependsOn'])) return true;

            $isIndependent = true;
            foreach ($this->dependencies as $target) {
                if (in_array($target['id'], $dep['dependsOn'])) {
                    $isIndependent = $isIndependent && !empty($target['syncKey']);
                }
            }

            return $isIndependent;
        });
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
}