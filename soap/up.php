<?php
$file = __DIR__ . '../../files/Lavrov1.jpg';

// First Example
$encodedFile = getDataURI($file);
$content =  str_replace(':', '', $encodedFile);
$content = str_replace('%', '', $content);
$content = str_replace('?', '', $content);


$parameters = [
    'Content' => $content,
];
//        echo ($return = $this->upload($data)) ? "File Uploaded : $return bytes"."\n" : "Error Uploading Files";

try {
    $ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

    $headerbody = array(
        'UsernameToken' => array(
            'Username' => '88b7e536-249a-4b65-a898-7e81db34ba9a',
            'Password' => '89f9577b-f693-49c0-840d-29a1338f1bfd'
        ),
        'ExtensionId' => 5000,
        'Name' => 'Lavrov1.jpg'
    );

    $header = new SOAPHeader($ns, 'Security', $headerbody);
    $client = new SoapClient("https://f19-fronter.itslbeta.com:4421/FileStreamService.svc?wsdl", array('trace' => 1));
    $client->__setSoapHeaders($header);
//            $xmlRequest = file_get_contents(__DIR__ . '/../../orgapi-xml-examples/UploadFile.xml');
    $func = $client->__getFunctions();
//            print_r($func);
//            $params = '<Content>
//                <inc:Include href="cid:Lavrov1.jpg" xmlns:inc="http://www.w3.org/2004/08/xop/include"/>
//            </Content>';

    $result = $client->UploadFile($parameters);
//            echo $upl . "\n";
////            $response = $client->__doRequest($xmlRequest, 'f19-fronter.itslbeta.com:4421', 'UploadFile', 1);
//            echo $response;
    file_put_contents('../../log/log.txt', $client->__getLastRequest() . "\n");
} catch (SOAPFault $f) {
    echo $client->__getLastRequest();
            print_r($f) . "\n";
}

function getDataURI($image, $mime = '')
{
    return base64_encode(file_get_contents($image));
//                mime_content_type($image) : $mime)  .
}