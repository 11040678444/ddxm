<?php
/**
 * Created by PhpStorm.
 * User: zgc
 * Date: 2018/8/29
 * Time: 15:47
 */

namespace app\wxshop\controller;

use think\Controller;

/**
 * 生成分享图片
 * Class QrcodeGoods
 * @package app\wxshop\controller
 */
class QrcodeGoods extends Controller
{


    /**
     *  不支持https
     *  extension=php_openssl.dll
    需要在对应的  php.ini  中设置     allow_url_include = On
     * 分享图片生成
     * @param $gData  商品数据，array
     * @param $codeName 二维码图片
     * @param $fileName string 保存文件名,默认空则直接输入图片
     */
    function createShareGoods($price,$goodsimg,$goodsTitle,$erimgurl,$fileName = ''){
        //创建画布
        $im = imagecreatetruecolor(474, 662);

        //填充画布背景色
        $color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $color);


        //字体文件
        $font_file = "qrcode/typeface/msyh.ttf";
//        $font_file = "E:\php\work\ddxmtp5\public\qrcode/typeface/msyh.ttf";
//        $font_file_bold = "E:\php\work\ddxmtp5\public\qrcode/typeface/DIN-Black.otf";

        //设定字体的颜色
        $font_color_1 = ImageColorAllocate ($im, 26,26,26);//标题颜色格式
        $font_color_2 = ImageColorAllocate ($im, 128,128,128);//扫码购买颜色格式
        $font_color_3 = ImageColorAllocate ($im, 252,90,90);//价格题颜色格式

        //商品图片
        list($g_w,$g_h) = getimagesize($goodsimg);
        $goodImg = $this->createImageFromFile($goodsimg);
        //绘制 商品图片
        imagecopyresized($im, $goodImg, 0, 0, 0, 0, 474, 474, $g_w, $g_h);

        if(mb_strlen($goodsTitle)<12){
            imagettftext($im, 16,0, 14, 446+58, $font_color_1 ,$font_file, $goodsTitle);
        }else{//大于23个字  文字换行
            $theTitle = $this->cn_row_substr($goodsTitle,2,12);
            imagettftext($im, 18,0, 14, 446+58, $font_color_1 ,$font_file, $theTitle[1]);
            imagettftext($im, 18,0, 14, 485+58, $font_color_1 ,$font_file, $theTitle[2]);
        }

        //绘制 二维码
        list($code_w,$code_h) = getimagesize($erimgurl);
        $codeImg = $this->createImageFromFile($erimgurl);
        $codeImgWidth = 120;
        if($codeImg!=false){
            imagecopyresized($im, $codeImg, 474-160+20, 446+60, 0, 0, $codeImgWidth, $codeImgWidth, $code_w, $code_h);
            imagettftext($im, 14,0, 474-140+20, 466+180, $font_color_2 ,$font_file, '扫码购买');
        }else{
            return false;
        }
        //绘制价格
        imagettftext($im, 24,0, 14, 466+180, $font_color_3 ,$font_file, $price);
        //输出图片
        if($fileName){
            imagepng ($im,$fileName);
        }else{
            Header("Content-Type: image/png");
            imagepng ($im);
        }

        //释放空间
        imagedestroy($im);
//        imagedestroy($goodImg);
        if($codeImg!=false){
            imagedestroy($codeImg);
        }

        return '成功';
    }

    /**
     * 分享好友--图片
     * @param string $fileName
     * @return bool|string
     */
    function createShareFriend($erimgurl,$fileName = ''){

        //创建画布
        $im = imagecreatetruecolor(750, 1206);

        //填充画布背景色
        $color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $color);

        $imgbg='http://picture.ddxm661.com/d6d4320190925163802628.png';
        list($g_w,$g_h) = getimagesize($imgbg);
        $imgbg = $this->createImageFromFile($imgbg);
        imagecopyresized($im, $imgbg, 0, 0, 0, 0, 750, 1206, $g_w, $g_h);

        //绘制 二维码
        list($code_w,$code_h) = getimagesize($erimgurl);
        $codeImg = $this->createImageFromFile($erimgurl);
        $codeImgWidth = 260;
        if($codeImg!=false){
            imagecopyresized($im, $codeImg, 240, 490, 0, 0, $codeImgWidth, $codeImgWidth, $code_w, $code_h);
//            imagecopyresized($im, $codeImg, 250, 580, 0, 0, $codeImgWidth, $codeImgWidth, $code_w, $code_h);
        }else{
            return false;
        }

        //输出图片
        if($fileName){
            imagepng ($im,$fileName);
        }else{
            Header("Content-Type: image/png");
            imagepng ($im);
        }

        //释放空间
        imagedestroy($im);
        imagedestroy($imgbg);
        if($codeImg!=false){
            imagedestroy($codeImg);
        }

        return '成功';
    }

    //分享好友
    function shareRetailFriend($erimgurl,$fileName = ''){

        //创建画布
        $im = imagecreatetruecolor(750, 1206);

        //填充画布背景色
        $color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $color);

        $imgbg='http://picture.ddxm661.com/20191128yaoqinghaoyou.png';
        list($g_w,$g_h) = getimagesize($imgbg);
        $imgbg = $this->createImageFromFile($imgbg);
        imagecopyresized($im, $imgbg, 0, 0, 0, 0, 750, 1206, $g_w, $g_h);

        //绘制 二维码
        list($code_w,$code_h) = getimagesize($erimgurl);
        $codeImg = $this->createImageFromFile($erimgurl);
        $codeImgWidth = 280;
        if($codeImg!=false){
            imagecopyresized($im, $codeImg, 240, 660, 0, 0, $codeImgWidth, $codeImgWidth, $code_w, $code_h);
//            imagecopyresized($im, $codeImg, 250, 580, 0, 0, $codeImgWidth, $codeImgWidth, $code_w, $code_h);
        }else{
            return false;
        }

        //输出图片
        if($fileName){
            imagepng ($im,$fileName);
        }else{
            Header("Content-Type: image/png");
            imagepng ($im);
        }

        //释放空间
        imagedestroy($im);
        imagedestroy($imgbg);
        if($codeImg!=false){
            imagedestroy($codeImg);
        }

        return '成功';
    }

    /**
     * 从图片文件创建Image资源
     * @param $file 图片文件，支持url
     * @return bool|resource    成功返回图片image资源，失败返回false
     */
    function createImageFromFile($file){
        if(preg_match('/http(s)?:\/\//',$file)){
//        if(preg_match('/http(s)?:',$file)){
            $fileSuffix = $this->getNetworkImgType($file);
        }else{
            $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
        }


        if(!$fileSuffix) return false;

        switch ($fileSuffix){
            case 'jpeg':
//                ini_set('gd.jpeg_ignore_warning', true);
//                ini_set('gd.jpeg_ignore_warning', 1);
                ini_set("memory_limit", "60M");
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'jpg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'png':
                $theImage = @imagecreatefrompng($file);
                break;
            case 'gif':
                $theImage = @imagecreatefromgif($file);
                break;
            default:
                $theImage = @imagecreatefromstring(file_get_contents($file));
                break;
        }

        return $theImage;
    }

    /**
     * 获取网络图片类型
     * @param $url  网络图片url,支持不带后缀名url
     * @return bool
     */
    function getNetworkImgType($url){
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);//设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //支持https
        curl_exec($ch);//执行curl会话
        $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
        curl_close($ch);//关闭资源连接

        if ($http_code['http_code'] == 200) {
            $theImgType = explode('/',$http_code['content_type']);

            if($theImgType[0] == 'image'){
                return $theImgType[1];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 分行连续截取字符串
     * @param $str  需要截取的字符串,UTF-8
     * @param int $row  截取的行数
     * @param int $number   每行截取的字数，中文长度
     * @param bool $suffix  最后行是否添加‘...’后缀
     * @return array    返回数组共$row个元素，下标1到$row
     */
    function cn_row_substr($str,$row = 1,$number = 10,$suffix = true){
        $result = array();
        for ($r=1;$r<=$row;$r++){
            $result[$r] = '';
        }

        $str = trim($str);
        if(!$str) return $result;

        $theStrlen = strlen($str);

        //每行实际字节长度
        $oneRowNum = $number * 3;
        for($r=1;$r<=$row;$r++){
            if($r == $row and $theStrlen > $r * $oneRowNum and $suffix){
                $result[$r] = $this->mg_cn_substr($str,$oneRowNum-2,($r-1)* $oneRowNum).'...';
            }else{
                $result[$r] = $this->mg_cn_substr($str,$oneRowNum,($r-1)* $oneRowNum);
            }
            if($theStrlen < $r * $oneRowNum) break;
        }

        return $result;
    }

    /**
     * 按字节截取utf-8字符串
     * 识别汉字全角符号，全角中文3个字节，半角英文1个字节
     * @param $str  需要切取的字符串
     * @param $len  截取长度[字节]
     * @param int $start    截取开始位置，默认0
     * @return string
     */
    function mg_cn_substr($str,$len,$start = 0){
        $q_str = '';
        $q_strlen = ($start + $len)>strlen($str) ? strlen($str) : ($start + $len);

        //如果start不为起始位置，若起始位置为乱码就按照UTF-8编码获取新start
        if($start and json_encode(substr($str,$start,1)) === false){
            for($a=0;$a<3;$a++){
                $new_start = $start + $a;
                $m_str = substr($str,$new_start,3);
                if(json_encode($m_str) !== false) {
                    $start = $new_start;
                    break;
                }
            }
        }

        //切取内容
        for($i=$start;$i<$q_strlen;$i++){
            //ord()函数取得substr()的第一个字符的ASCII码，如果大于0xa0的话则是中文字符
            if(ord(substr($str,$i,1))>0xa0){
                $q_str .= substr($str,$i,3);
                $i+=2;
            }else{
                $q_str .= substr($str,$i,1);
            }
        }
        return $q_str;
    }


}