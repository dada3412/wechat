<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 2017/12/21
 * Time: 下午10:40
 */

require_once './WeChat.Class.php';

$wechat = new WeChat(APP_ID, APP_SECRET, TOKEN);

//echo $wechat->getAccessToken();
$qrCode = $wechat->getTicket(111,WeChat::QR_SCENE_TEMP,60);
header('content-type: image/jpg');
echo $qrCode;