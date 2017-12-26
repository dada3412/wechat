<?php

/**
 * Created by PhpStorm.
 * User: niko
 * Date: 2017/12/21
 * Time: 下午9:50
 */
require_once './GlobalParam.php';

class WeChat
{
    private $_appID;
    private $_appsecret;
    private $_token;

    const QR_SCENE_TEMP = 1; //临时二维码
    const QR_SCENE = 2;  //永久

    const ACCESS_TOKEN_TYPE = 1;
    const QRCODE_TYPE = 2;

    public function __construct($id, $secret,$token)
    {
        $this->_appID = $id;
        $this->_appsecret = $secret;
        $this->_token = $token;
    }

    public function firstCheck(){
        if ($this->_checkSignature()){
            echo $_GET['echostr'];
        }
    }

    private function _checkSignature(){
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];

        $tmpArr = array($this->_token,$timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($signature == $tmpStr){
            return true;
        }else{
            return false;
        }
    }

    public function getAccessToken(){
        //查看token文件是否存在或者过期
        $expire = 7200;
        if (file_exists(ACCESS_FILE) && time() - filemtime(ACCESS_FILE) < $expire){
             return file_get_contents(ACCESS_FILE);
        };

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->_appID}&secret={$this->_appsecret}";
        $content = $this->processURL($url, true);
        $result = json_decode($content);
        if (!$result->errcode){
            file_put_contents(ACCESS_FILE, $result->access_token);
            return $result->access_token;
        }else{
            return fasle;
        }
    }

    private function processURL($url, $ssl=false, $isPost=false, $content=''){
        $link = curl_init();

        curl_setopt($link, CURLOPT_URL, $url);
        curl_setopt($link, CURLOPT_HEADER, false);
        curl_setopt($link, CURLOPT_RETURNTRANSFER, true);

        if ($ssl){
            curl_setopt($link, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($link, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($isPost){
            curl_setopt($link, CURLOPT_POST, true);
            curl_setopt($link, CURLOPT_POSTFIELDS, $content);
        }

        $content = curl_exec($link);

        return $content;

    }

    public function getTicket($content, $type = self::QR_SCENE_TEMP, $expire = 10){
        $accessToken = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$accessToken;
       // echo $url.'<hr>';
        $dataArr = array();
        switch ($type){
            case self::QR_SCENE_TEMP :
                $dataArr['action_name'] = 'QR_SCENE';
                $dataArr['action_info']['scene']['scene_id'] = $content;
                $dataArr['expire_seconds'] = $expire;
                break;

            case self::QR_SCENE :
                $dataArr['action_name'] = 'QR_LIMIT_SCENE';
                $dataArr['action_info']['scene']['scene_id'] = $content;
                break;

            default :
                return false;
        }
        $data = json_encode($dataArr);

        $content = $this->processURL($url,true, true, $data);
        $result = json_decode($content);
        if (!$result->errcode){
            $tickt = $result->ticket;
            $tickt = urlencode($tickt);
            $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$tickt;
            $result = $this->processURL($url);
            return $result;
        }else{
            return false;
        }

    }


    public function getMsg(){
        //$xmlStr = $HTTP_RAW_POST_DATA;
        //var_dump($xmlStr);
        //echo '<hr>';
        $xmlStr = file_get_contents("php://input");

        if (empty($xmlStr)){
            exit('');
        }

        libxml_disable_entity_loader(true);
        $requestXml = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        switch ($requestXml->MsgType){
            case 'event' :
                $event = $requestXml->Event;
                //echo 'event = '.$event.'\n';
                if ($event == 'subscribe'){
                    $this->_doSubscribe($requestXml);
                }
                break;

            default :
                break;
        }

    }

    private function _doSubscribe($requestXml){
        $text = "<xml><ToUserName>< ![%s] ]></ToUserName> <FromUserName>< ![CDATA[%s] ]></FromUserName> <CreateTime>%s</CreateTime> <MsgType>< ![CDATA[text] ]></MsgType> <Content>< ![CDATA[%s] ]></Content> </xml>";
        $text = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>';

        $content = "你说你是不是有病啊!";



        $time = time();

        $response = sprintf($text, $requestXml->FromUserName, $requestXml->ToUserName, $time, $content);
        //echo $response;
        exit($response);
    }

}

