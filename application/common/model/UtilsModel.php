<?php
namespace app\common\model;
/**
 * Class Utils
 * @package App
 */
class UtilsModel
{
    /**
     * @param null $data
     * @param int $status
     * @param string $message
     * @return Response
     */
    public static function render($data = null, $status = 0, $message = '')
    {
        return response()->json(compact('data', 'message', 'status'), 200)->header('Content-Type', 'application/json');
    }
    /**
     * @param $url
     * @return mixed
     */
    public static function httpGet($url){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        // CURLOPT_RETURNTRANSFER  设置是否有返回值
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//当请求https的数据时，会要求证书，这时候，加上下面这两个参数，规避ssl的证书检查
        //执行完以后的返回值
        $response = curl_exec($curl);
        //释放curl
        curl_close($curl);
        return $response;
    }
    /**
     * @param $url
     * @param $postbody
     * @return mixed
     */
    public static function httpPost($url,$postbody){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        // CURLOPT_RETURNTRANSFER  设置是否有返回值
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_POST,true);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$postbody);//get方式通过网址传递参数，但post通过CURLOPT_POSTFIELDS传递参数，但这仅仅只针对于curl而言
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//当请求https的数据时，会要求证书，这时候，加上下面这两个参数，规避ssl的证书检查
        //执行完以后的返回值
        $response = curl_exec($curl);
        //释放curl
        curl_close($curl);
        return $response;
    }
    /**
     * @param $arr
     * @return null|string
     */
    public static function arrayToXml($arr)
    {
        if (!empty($arr) && is_array($arr)) {
            $xml = "<xml>";
            foreach ($arr as $key => $value) {
                if (is_numeric($value)) {
                    $xml .= "<" . $key . ">" . $value . "</" . $key . ">";
                } else {
                    $xml .= "<" . $key . "><![CDATA[". $value ."]]></" . $key . ">";
                }
            }
            $xml .= "</xml>";
            return $xml;
        } else {
            return null;
        }
    }
    /**
     * @param $xml
     * @return mixed
     */
    public static function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    /***
     * @param $vars
     * @param $url
     * @param int $second
     * @param array $aHeader
     * @return bool|string
     */
    public function wx_curl($vars,$url,$second = 30, $aHeader = array()) {
        $isdir = ROOT_PATH."cert/";//证书位置
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);//设置执行最长秒数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);// 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');//证书类型
        curl_setopt($ch, CURLOPT_SSLCERT,  '/www/cert/apiclient_cert.pem');//证书位置
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');//CURLOPT_SSLKEY中规定的私钥的加密类型
        curl_setopt($ch, CURLOPT_SSLKEY,  '/www/cert/apiclient_key.pem');//证书位置
        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);//设置头部
        }
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);//全部数据使用HTTP协议中的"POST"操作来发送

        $data = curl_exec($ch);//执行回话
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }

    /***
     * 获取客户端ip
     */
    public function getUserIpAddr(){
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /***
     * 获取某个时间戳的天数小时数分数秒数
     */
    function time2string($second){
        $day = floor($second/(3600*24));
        $second = $second%(3600*24);//除去整天之后剩余的时间
        $hour = floor($second/3600);
        $second = $second%3600;//除去整小时之后剩余的时间
        $minute = floor($second/60);
        $second = $second%60;//除去整分钟之后剩余的时间
        return ['day'=>$day,'hour'=>$hour,'minute'=>$minute,'second'=>$second];
    }
}