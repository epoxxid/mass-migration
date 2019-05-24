<?php

require_once 'XmlHelper.php';

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

    public function __construct()
    {
        $this->initSoapClient();
    }

    private function initSoapClient()
    {
        $this->soapClient = new SoapClient($this->wsdlUri, array('trace' => true));
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


//        $msgBody = $this->prepareMessageBody($type, $messageXml);
        try {

//            $dataMessage = new ArrayObject();
//
//            $Data = new SoapVar($messageXml, XSD_ANYXML, null, null, 'Data');
//            $Type = new SoapVar($type, XSD_STRING, null, null, 'Type');
//            $dataMessage->append($Data);
//            $dataMessage->append($Type);
//
//            $dataMessage = new SoapVar($dataMessage, SOAP_ENC_ARRAY, null,null, 'dataMessage');
//            $dataMessage = new SoapVar(array($Data, $Type), SOAP_ENC_ARRAY, null, null, 'messageData');

//            $dataMessage = $this->prepareMessageBody($type, $messageXml);

            $Data = new stdClass();

            $dataMessage = new stdClass();
            $dataMessage->Data = (string) $messageXml;
            $dataMessage->Type = '123';

            $request = new stdClass();
            $request->dataMessage = new SoapVar($dataMessage, SOAP_ENC_OBJECT, null, null, 'dataMessage', $this->msgDataNS);

            /** @noinspection PhpUndefinedMethodInspection */
            $this->soapClient->AddMessage($request);
        } catch (\Throwable $e) {
            $this->log($e->getMessage());
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
        $xml = $this->formatXml($this->soapClient->__getLastRequest());
        $xml = $encodeEntities ? htmlentities($xml) : $xml;
        echo "\n\n======== Request ==========\n$xml\n";
    }

    private function formatXml($xml)
    {
        $xml = preg_replace('~<[^/].+?>~', "\n$0", $xml);
        return preg_replace('~</.+?>~', "$0\n", $xml);
    }

    public function dumpResponseXml($encodeEntities)
    {
        $xml = $this->formatXml($this->soapClient->__getLastResponse());
        $xml = $encodeEntities ? htmlentities($xml) : $xml;
        echo "\n\n======== Response ==========\n$xml\n";
    }

    private function prepareMessageBody($type, $messageXml)
    {
        $msgContainerXml = "<dataMessage xmlns:mdata='{$this->msgDataNS}'/>";
        $dataMessage = new SimpleXMLElement($msgContainerXml);
        $Data = $dataMessage->addChild('Data', null, $this->msgDataNS);

//        XmlHelper::addCDATAChild($Data, $messageXml);

        $dataMessage->addChild('Type', $type, $this->msgDataNS);

        return XmlHelper::convertToPlainXml($dataMessage);
    }
}
