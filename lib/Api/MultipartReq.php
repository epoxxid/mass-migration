<?php

class MultipartReq
{
//Метод формирования части составного запроса
    public static function PartPost()
    {
        $body = 'Content-Type: application/xop+xml; charset=UTF-8; type="text/xml"' . "\r\n" .
            'Content-Transfer-Encoding: 8bit' . "\r\n" .
            'Content-ID: <uniq@id.com>'. "\r\n";
        return $body;
    }

// Метод формирующий тело POST запроса из переданного массива
    public static function Get(array $post, $delimiter = '-------------0123456789')
    {
        $ret = '';
        $file = base64_encode($post['file']->content);
        $file = str_replace(':', '', $file);
        $file = str_replace('%', '', $file);
        $file = str_replace('?', '', $file);


        if (is_array($post) && !empty($post)) {
            $bool = false;
// Проверяем есть ли среди элементов массива файл
            foreach ($post as $val) if ($val instanceof FileProps) {
                $bool = true;
                break;
            };
            if ($bool) {
                $ret .= '--' . $delimiter . "\r\n";
                $ret .= self::PartPost(). "\r\n";
                $ret .= $post['field'] . "\r\n";
                $ret .= '--' . $delimiter . "\r\n" .
                    'Content-Type:' . $post['file']->mime . '; name=' . $post['file']->name . "\r\n" .
                    'Content-Transfer-Encoding: binary' . "\r\n" .
                    'Content-ID: <' . $post['file']->name . '>' . "\r\n" .
                    'Content-Disposition: attachment; name="' . $post['file']->name . '"; filename="' . $post['file']->name . "\r\n\n";
                $ret .= $file . "\r\n";
                $ret .= "--" . $delimiter . "--\r\n";
            } else {
                $ret = http_build_query($post);
            }
        } else throw new \Exception('Error input param!');
//        print_r($ret);
        return $ret;
    }
}