<?php


class OrgApiFolderCreator
{
    private $apiClient;

    /**
     * OrgApiFolderCreator constructor.
     * @param OrgApiClient $apiClient
     */
    public function __construct($apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function createFolder(array $params = array())
    {

    }
}
