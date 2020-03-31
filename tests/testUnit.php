<?php

use PHPUnit\Framework\TestCase;

class testUnit extends TestCase
{

    const Ip = "https://pay.iov-smart.net";

    function postUrl($url, $data, $header = array("Content-type:application/json;charset='utf-8'", "Accept:application/json"), $needToJson = true)
    {
        if ($needToJson) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        var_dump($output);
        curl_close($curl);
        return json_decode($output, true);
    }

    public function testLogin()
    {
        $res = $this->postUrl(
            self::Ip . '/api/v2/admin/4/login',

            [
                'username' => 'cvltest',
                'password' => 'cd9b72ec8b81f386e33942b4f97449ac'
            ],

            ['Content-Type:application/json']

        );
        $this->assertEquals("1", $res["code"], $res["msg"]);
        return $res;
    }
}

