<?php


class MMConfig
{
    private $wsdlUri = 'https://f19-fronter.itslbeta.com:4421/DataService.svc?wsdl';
    private $apiUserName = '88b7e536-249a-4b65-a898-7e81db34ba9a';
    private $apiPassword = '89f9577b-f693-49c0-840d-29a1338f1bfd';
    private $logLevel = 'error';

    public function __construct($iniFile = null)
    {
        if ($iniFile && is_file($iniFile)) {
            $this->loadIniFile($iniFile);
        }
    }

    public function getWSDLUri()
    {
        return (string) $this->wsdlUri;
    }

    public function getApiUserName()
    {
        return (string) $this->apiUserName;
    }

    public function getApiPassword()
    {
        return (string) $this->apiPassword;
    }

    public function getLogLevel()
    {
        return (string) $this->logLevel;
    }

    private function loadIniFile($iniFile)
    {
        $data = parse_ini_file($iniFile);
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}