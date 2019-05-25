<?php

require_once 'XmlHelper.php';
require 'SoapClientDebugger.php';

class OrgApiClient
{
    /**
     * @var SoapClient
     */
    private $soapClient;

    private $wsdlUri = 'https://f19-fronter.itslbeta.com:4421/DataService.svc?wsdl';

    private $apiUserName = '88b7e536-249a-4b65-a898-7e81db34ba9a';
    private $apiPassword = '89f9577b-f693-49c0-840d-29a1338f1bfd';

    private $soapHeaderNS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private $msgDataNS = 'http://schemas.datacontract.org/2004/07/Itslearning.Integration.ContentImport.Services.Entities';

    public function __construct($debug = false)
    {
        $this->initSoapClient($debug);
    }

    private function initSoapClient($debug)
    {
        if ($debug) {
            $this->soapClient = new SoapClientDebugger($this->wsdlUri, array('trace' => true));
        } else {
            $this->soapClient = new SoapClient($this->wsdlUri, array('trace' => true));
        }
        $this->soapClient->__setSoapHeaders($this->buildSoapHeader());
    }

    /**
     * @return SoapHeader
     */
    private function buildSoapHeader()
    {
        $userName = new SoapVar(
            $this->apiUserName,
            XSD_STRING,
            null,
            null,
            'Username',
            $this->soapHeaderNS
        );

        $password = new SoapVar(
            $this->apiPassword,
            XSD_STRING,
            null,
            null,
            'Password',
            $this->soapHeaderNS
        );

        $token = new SoapVar(
            array($userName, $password),
            SOAP_ENC_OBJECT,
            null,
            null,
            'UsernameToken',
            $this->soapHeaderNS
        );

        return new SoapHeader(
            $this->soapHeaderNS,
            'Security',
            new SoapVar(array($token), SOAP_ENC_OBJECT),
            '1'
        );
    }

    /**
     * @param int $type
     * @param string $messageXml
     */
    public function sendMessage($type, $messageXml)
    {
        $this->log('>>> Start sending XML message');

        try {
            $dataMessage = new stdClass();
            $dataMessage->Data = new SoapVar(
                $messageXml,
                XSD_STRING,
                null,
                null,
                'Data',
                $this->msgDataNS
            );

            $dataMessage->Type = new SoapVar(
                $type,
                XSD_INT,
                null,
                null,
                'Type',
                $this->msgDataNS
            );

            $request = new stdClass();
            $request->dataMessage = new SoapVar(
                $dataMessage,
                SOAP_ENC_OBJECT,
                null,
                null,
                'dataMessage',
                $this->msgDataNS
            );

            /** @noinspection PhpUndefinedMethodInspection */
            $response = $this->soapClient->AddMessage($request);

            $result = $response->AddMessageResult;

            if ($result->Status === 'InQueue') {
                $this->log("Message added to queue with ID = {$result->MessageId}");
                return true;
            }
        } catch (\Throwable $e) {
            $this->log('ERROR: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param $str
     */
    private function log($str)
    {
        echo "$str\n";
    }

    public function getMessageTypes()
    {
        $this->log('>>> Get list of message types');
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->soapClient->GetMessageTypes();
        } catch (\Throwable $e) {
            $this->log($e->getMessage());
        }
    }

    public function dumpRequestXml($encodeEntities)
    {
        echo "\n\n======== Request ==========\n";
        echo XmlHelper::formatXml($this->soapClient->__getLastRequest(), $encodeEntities);
    }

    public function dumpResponseXml($encodeEntities)
    {
        echo "\n\n======== Response ==========\n";
        echo XmlHelper::formatXml($this->soapClient->__getLastResponse(), $encodeEntities);
    }
}
