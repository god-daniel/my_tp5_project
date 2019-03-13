<?php
/**
 * Created by ecitlm.
 * User: ecitlm
 * Date: 2017/9/23
 * Time: 00:18
 */

namespace app\api\controller;
use think\Controller;
use Endroid\QrCode\QrCode;

class Qr extends Controller{

    public function index(){
        var_dump(112);
    }
    /*
    https://github.com/endroid/qr-code/tree/2.x
    */
    public function qrcode(){
        $url = '123';
        if(input('param.data')){
            $url = input('param.data');
        }
        $qrCode = new QrCode($url);
        header('Content-Type: '.$qrCode->getContentType());
        echo $qrCode->writeString();
    }
}