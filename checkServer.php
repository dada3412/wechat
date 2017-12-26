<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 2017/12/25
 * Time: 下午10:41
 */
//ini_set('display_errors',1);
//error_reporting(E_ALL);

require_once './WeChat.Class.php';

$wechat = new WeChat(APP_ID, APP_SECRET, TOKEN);
//$wechat->firstCheck();
$wechat->getMsg();