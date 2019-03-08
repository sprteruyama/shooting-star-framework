<?php

use ShootingStar\Base;

class HttpUtils extends Base
{
    public static function postJson($url, $data)
    {
        $body = json_encode($data);
        $header = array(
            "Content-Type: application/json",
            "Content-Length: " . strlen($body)
        );
        $context = array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => $body,
            )
        );
        $result = file_get_contents($url, false, stream_context_create($context));
        if ($result) {
            $result = json_decode($result, true);
        }
        return $result;
    }

    public static function post($url, $data)
    {
        $body = http_build_query($data, "", "&");
        $header = array(
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: " . strlen($body)
        );
        $context = array(
            "http" => array(
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => $body,
            )
        );
        return file_get_contents($url, false, stream_context_create($context));
    }
}
