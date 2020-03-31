<?php

namespace Common;
class Helper
{

    function getUrl($url, $headerArray = array("Content-type:application/json;", "Accept:application/json", "Expect: "))
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        if ($headerArray) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        }
        $output = curl_exec($ch);
        curl_getinfo($ch, CURLINFO_HEADER_OUT);
        return $output;
    }

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
        curl_close($curl);
        return json_decode($output, true);
    }

    function delUrl($url, $headerArray = array("Content-type:application/json;", "Accept:application/json")){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        //设置头
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray); //设置请求头
        curl_setopt($ch, CURLOPT_USERAGENT,  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.98 Safari/537.36');

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//SSL认证。
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;

    }

    function putUrl($url = '', $data = false,$header=array("Content-type:application/json;", "Accept:application/json")){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); //定义请求地址
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);//定义是否直接输出返回流
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); //定义请求类型，必须为大写
        //curl_setopt($ch, CURLOPT_HEADER,1); //定义是否显示状态头 1：显示 ； 0：不显示
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//定义header
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //定义提交的数据
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
        $res = curl_exec($ch);


        curl_close($ch);//关闭
        return $res;
    }

}

