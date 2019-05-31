<?php
require_once '../lib/Api/FileProps.php';
require_once '../lib/Api/MultipartReq.php';

$xml = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
  <s:Header><wsse:Security s:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"><wsse:UsernameToken u:Id="UsernameToken-AD62546C58F5029C2215592963701185"><wsse:Username>88b7e536-249a-4b65-a898-7e81db34ba9a</wsse:Username><wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">89f9577b-f693-49c0-840d-29a1338f1bfd</wsse:Password><wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">ASTLuVDlx8L7otnaxdFz/Q==</wsse:Nonce><u:Created>2019-05-31T09:52:50.118Z</u:Created></wsse:UsernameToken></wsse:Security>
    <h:ExtensionId xmlns:h="http://tempuri.org/">5000</h:ExtensionId>
    <h:Name xmlns:h="http://tempuri.org/">Lavrov1.jpg</h:Name>
  </s:Header>
  <s:Body>
    <StreamMessage xmlns="http://tempuri.org/">
      <Content>
        <xop:Include href="cid:Lavrov1.jpg" xmlns:xop="http://www.w3.org/2004/08/xop/include"/>
      </Content>
    </StreamMessage>
  </s:Body>
</s:Envelope>';
// Подключаем класс-контейнер содержимого файла
// Подключаем класс для формирования тела POST запроса

// Генерируем уникальную строку для разделения частей POST запроса
$delimiter = '-------------' . uniqid();

// Формируем объект FileProps содержащий файл
$file = new FileProps('/var/www/gearman.loc/files/Lavrov1.jpg');

// Формируем тело POST запроса
$post = MultipartReq::Get(array('field' => $xml, 'file' => $file), $delimiter);

// Инициализируем  CURL
$ch = curl_init();

// Указываем на какой ресурс передаем файл
curl_setopt($ch, CURLOPT_URL, 'https://f19-fronter.itslbeta.com:4421/FileStreamService.svc');
// Указываем, что будет осуществляться POST запрос
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Передаем тело POST запроса
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);

/* Указываем дополнительные данные для заголовка:
Content-Type - тип содержимого,
boundary - разделитель и
Content-Length - длина тела сообщения */
$imgSize = filesize('/var/www/gearman.loc/files/Lavrov1.jpg');
$contentLength = $imgSize + strlen($xml);

$headers = array(
    'Content-Type: multipart/related; type="application/xop+xml"; start="<uniq@id.com>"; start-info="text/xml" boundary=' . $delimiter,
    'Content-Length: ' . $contentLength,
    'SOAPAction: "http://tempuri.org/IFileStreamService/UploadFile"',
    'MIME-Version: 1.0',
    'Accept-Encoding: gzip,deflate',
    'Host: f19-fronter.itslbeta.com:4421',
    'Connection: Keep-Alive',
    'User-Agent: Apache-HttpClient/4.1.1 (java 1.5)'

);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Отправляем POST запрос на удаленный Web сервер
$errors = curl_error($ch);
$res = curl_exec($ch);
if(curl_errno($ch)){
    echo 'Curl error: ' . curl_error($ch);
}
print_r(curl_getinfo($ch));
//print_r($contentLength);

echo $res ."\n";
//echo strlen($post);
//var_dump($errors);