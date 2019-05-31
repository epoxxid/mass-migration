<?php

require dirname(__DIR__) . '/Helpers/XmlHelper.php';


class ApiFileUploader
{
    /** @var string */
    private $wsdl = 'https://f19-fronter.itslbeta.com:4421/FileStreamService.svc?wsdl';

    /** @var string */
    private $securityHeaderNS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    /** @var string  */
    private $extraHeaderNS = 'http://tempuri.org';

    /**
     * @var MMLogger
     */
    private $logger;
    /**
     * @var SoapClient
     */
    private $apiClient;
    /**
     * @var MMConfig
     */
    private $config;

    public function __construct(MMConfig $config, MMLogger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function uploadFile($fileName)
    {
        $this->logger->dbg('Attempt to upload file ' . $fileName, __METHOD__);

        try {
            $this->initApiClient($fileName);

            $Content = new stdClass();
            $xml = sprintf('<inc:Include href="cid:%s" xmlns:inc="http://www.w3.org/2004/08/xop/include"/>', $fileName);
            $Content->content = new SoapVar($xml, XSD_ANYXML);

            $request = new stdClass();
            $request->Content = new SoapVar($Content, SOAP_ENC_OBJECT);

            $attachment = new ImageObj(__DIR__ . '/../../files/'.$fileName);
            $param = new SoapVar($attachment, SOAP_ENC_OBJECT, "attachment");
            $param = new SoapParam($param, "param");

            /** @noinspection PhpUndefinedMethodInspection */
            $this->apiClient->UploadFile($request);

        } catch (\Throwable $e) {
//            $this->logger->error($e->getMessage(), __METHOD__);.
            print_r($e);
        }
//        $this->logger->dbgXml(XmlHelper::formatXml($this->apiClient->__getLastRequest()), __METHOD__);
//        $this->logger->dbgXml($this->apiClient->__getLastResponse(), __METHOD__);
    }


    private function initApiClient($fileName)
    {
        $this->logger->dbg('Init API client', __METHOD__);
        $this->apiClient = new SoapClient($this->wsdl, array('trace'=>1, 'stream_context' =>
            stream_context_create(array('http'=>array(
                'header'=> 'Accept-Encoding: gzip,deflate'.
                    'SOAPAction: http://tempuri.org/IFileStreamService/UploadFile'.
                    'Content-Type: image/jpeg; name=Lavrov1.jpg'.
                    'Content-Transfer-Encoding: binary'.
                    'Content-ID: <Lavrov1.jpg>'.
                    'Content-Disposition: attachment; name="Lavrov1.jpg"; filename="Lavrov1.jpg'.
                    'MIME-Version: 1.0'.
                    'Content-Length: 128611'
            )))));
        $this->initHeaders($fileName);
    }

    /**
     * Build and load valid SOAP header
     *
     * @param MMConfig $config
     * @return bool
     */
    private function initHeaders($fileName)
    {
        $userName = new SoapVar(
            $this->config->getApiUserName(),
            XSD_STRING,
            null,
            null,
            'Username',
            $this->securityHeaderNS
        );

        $password = new SoapVar(
            $this->config->getApiPassword(),
            XSD_STRING,
            null,
            null,
            'Password',
            $this->securityHeaderNS
        );

        $token = new SoapVar(
            array($userName, $password),
            SOAP_ENC_OBJECT,
            null,
            null,
            'UsernameToken',
            $this->securityHeaderNS
        );

        $securityHeader = new SoapHeader(
            $this->securityHeaderNS,
            'Security',
            new SoapVar(array($token), SOAP_ENC_OBJECT),
            '1'
        );

        $extensionHeader = new SoapHeader(
            $this->extraHeaderNS,
            'ExtensionId',
            new SoapVar(5000, XSD_INT)
        );

        $nameHeader = new SoapHeader(
            $this->extraHeaderNS,
            'Name',
            new SoapVar($fileName, XSD_STRING)
        );

        return $this->apiClient->__setSoapHeaders(array(
            $securityHeader,
            $extensionHeader,
            $nameHeader
        ));
    }
}

class ImageObj{
    function __construct($file, $mime = "") {
        $this->file = $file;
        $this->name = basename($file);
        if (function_exists('mime_content_type')) {
            $this->mime = mime_content_type($file);
        } elseif (function_exists('finfo_open')) {
            $this->mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
        } else {
            $this->mime = $mime;
        }

        $this->encoding = "base64";
        $this->data = base64_encode(file_get_contents($file));
    }
}