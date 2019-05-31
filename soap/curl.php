<?php

$ch = curl_init();
// Указываем на какой ресурс передаем файл
curl_setopt($ch, CURLOPT_URL, 'https://f19-fronter.itslbeta.com:4421/FileStreamService.svc');
// Указываем, что будет осуществляться POST запрос
curl_setopt($ch, CURLOPT_POST, 1);
// Передаем тело POST запроса
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);

/* Указываем дополнительные данные для заголовка:
Content-Type - тип содержимого,
boundary - разделитель и
Content-Length - длина тела сообщения */
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data; boundary=' . $delimiter,
'Content-Length: ' . strlen($post)));