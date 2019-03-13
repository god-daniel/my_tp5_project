<?php
/**
 * Created by ecitlm.
 * User: ecitlm
 * Date: 2017/9/23
 * Time: 00:18
 */

namespace app\api\controller;
use think\Controller;
use think\Db;
use think\facade\Cache;
use think\View;
use think\Loader;

class Qrcode extends Controller{

    public function index(){
        var_dump(112);
    }
    /*
    * png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 4, $margin = 4, $saveandprint=false, $back_color = 0xFFFFFF, $fore_color = 0x000000)
    * 参数说明:
    * $text 就是url参数
    * $outfile 默认否，不生成文件，只返回二维码图片，否则需要给出保存路径
    * $level 二维码容错率，默认L(7%)、M(15%)、Q(25%)、H(30%)
    * $size 二维码图片大小，默认4
    * $margin 二维码空白区域大小
    * $saveabdprint 二维码保存并显示，$outfile必须传路径
    * $back_color 背景颜色
    * $fore_color 绘制二维码的颜色
    * tip:颜色必须传16进制的色值，并把“#”替换为“0x”; 如 #FFFFFF => 0xFFFFFF
    */
    public function qrcode(){
        Loader::Vendor('Phpqrcode.phpqrcode');
        //import('phpqrcode.phpqrcode', EXTEND_PATH);
        $url = '';
        if(input('param.data')){
            $url = input('param.data');
        }
        $size=4;    //图片大小
        $errorCorrectionLevel = "Q"; // 容错级别：L、M、Q、H
        $matrixPointSize = "8"; // 点的大小：1到10
        //实例化
        $qr = new \QRcode();
        //会清除缓冲区的内容，并将缓冲区关闭，但不会输出内容。
        ob_end_clean();
        //输入二维码
        $qr::png($url, false, $errorCorrectionLevel, $matrixPointSize);

    }
}