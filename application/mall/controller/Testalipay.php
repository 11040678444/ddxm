<?php
namespace app\mall\controller;

use app\wxshop\alipay\model\builder\AlipayTradePayContentBuilder;
use app\wxshop\alipay\model\builder\ExtendParams;
use app\wxshop\alipay\model\builder\GoodsDetail;
use app\wxshop\alipay\service\AlipayTradeService;

/**
 * 测试 支付宝当面支付
 * Class TestAlipay
 * @package app\mall\controller
 */
class Testalipay
{

    public function index(){
        $auth_code = input('auth_code');
        if(empty($auth_code)){
            return;
        }
       $this->pay($auth_code);
    }

    private function pay($auth_code){

            // (必填) 商户网站订单系统中唯一订单号，64个字符以内，只能包含字母、数字、下划线，
            // 需保证商户系统端不能重复，建议通过数据库sequence生成，
            $outTradeNo = "barpay" . date('Ymdhis') . mt_rand(100, 1000);

            // (必填) 订单标题，粗略描述用户的支付目的。如“XX品牌XXX门店消费”
            $subject = "江与城门店";

            // (必填) 订单总金额，单位为元，不能超过1亿元
            // 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
            $totalAmount = 0.01;

            // (必填) 付款条码，用户支付宝钱包手机app点击“付款”产生的付款条码
            $authCode = $auth_code; //28开头18位数字

            // (可选,根据需要使用) 订单可打折金额，可以配合商家平台配置折扣活动，如果订单部分商品参与打折，可以将部分商品总价填写至此字段，默认全部商品可打折
            // 如果该值未传入,但传入了【订单总金额】,【不可打折金额】 则该值默认为【订单总金额】- 【不可打折金额】
            //String discountableAmount = "1.00"; //

            // (可选) 订单不可打折金额，可以配合商家平台配置折扣活动，如果酒水不参与打折，则将对应金额填写至此字段
            // 如果该值未传入,但传入了【订单总金额】,【打折金额】,则该值默认为【订单总金额】-【打折金额】
            $undiscountableAmount = "";

            // 卖家支付宝账号ID，用于支持一个签约账号下支持打款到不同的收款账号，(打款到sellerId对应的支付宝账号)
            // 如果该字段为空，则默认为与支付宝签约的商户的PID，也就是appid对应的PID
            $sellerId = "";

            // 订单描述，可以对交易或商品进行一个详细地描述，比如填写"购买商品2件共15.00元"
            $body = "购买商品2件共15.00元";

            // 这里传入 门店 服务员的 用户ID
            //商户操作员编号，添加此参数可以为商户操作员做销售统计
            $operatorId = "test_operator_id";

            // (可选) 商户门店编号，通过门店号和商家后台可以配置精准到门店的折扣信息，详询支付宝技术支持
            $storeId = "";

            // 支付宝的店铺编号
            $alipayStoreId = "test_alipay_store_id";

            // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，详情请咨询支付宝技术支持
            $providerId = ""; //系统商pid,作为系统商返佣数据提取的依据
            $extendParams = new ExtendParams();
            $extendParams->setSysServiceProviderId($providerId);
            $extendParamsArr = $extendParams->getExtendParams();

            // 支付超时，线下扫码交易定义为5分钟
            $timeExpress = "5m";

            // 商品明细列表，需填写购买商品详细信息，
            $goodsDetailList = array();

            // 创建一个商品信息，参数含义分别为商品id（使用国标）、名称、单价（单位为分）、数量，如果需要添加商品类别，详见GoodsDetail
            $goods1 = new GoodsDetail();
            $goods1->setGoodsId("good_id001");
            $goods1->setGoodsName("XXX商品1");
            $goods1->setPrice(3000);
            $goods1->setQuantity(1);
            //得到商品1明细数组
            $goods1Arr = $goods1->getGoodsDetail();

            // 继续创建并添加第一条商品信息，用户购买的产品为“xx牙刷”，单价为5.05元，购买了两件
            $goods2 = new GoodsDetail();
            $goods2->setGoodsId("good_id002");
            $goods2->setGoodsName("XXX商品2");
            $goods2->setPrice(1000);
            $goods2->setQuantity(1);
            //得到商品1明细数组
            $goods2Arr = $goods2->getGoodsDetail();

            $goodsDetailList = array($goods1Arr, $goods2Arr);

            //第三方应用授权令牌,商户授权系统商开发模式下使用
            $appAuthToken = "";//根据真实值填写

            // 创建请求builder，设置请求参数
            $barPayRequestBuilder = new AlipayTradePayContentBuilder();
            $barPayRequestBuilder->setOutTradeNo($outTradeNo);
            $barPayRequestBuilder->setTotalAmount($totalAmount);
            $barPayRequestBuilder->setAuthCode($authCode);
            $barPayRequestBuilder->setTimeExpress($timeExpress);
            $barPayRequestBuilder->setSubject($subject);
            $barPayRequestBuilder->setBody($body);
            $barPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
            $barPayRequestBuilder->setExtendParams($extendParamsArr);
            $barPayRequestBuilder->setGoodsDetailList($goodsDetailList);
//            $barPayRequestBuilder->setStoreId($storeId);
//            $barPayRequestBuilder->setOperatorId($operatorId);
//            $barPayRequestBuilder->setAlipayStoreId($alipayStoreId);

            $barPayRequestBuilder->setAppAuthToken($appAuthToken);

//        return [
//            "appid" => '2018042002585934', //https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写添加了“当面付”的应用的APPID
//            "notifyUrl" => 'http://dd.ddxm661.com/wechat/pay/alicallback',     //付款成功后的异步回调地
//            "rsaPrivateKey" =>'MIIEpAIBAAKCAQEAw0sI1j8EP7rPoMONRsXLfFfGx+molBlPtKHDwP2P0LuhN3W+Ndum03yxzaaCK2TkNs+V52DeP4MA2zUZ3BVKMj4RDJx9HrqMzRufq4rSY200ojBvKZelhsB9yAjFFo8462m9Wy2jAXKAoGdoZHRY+4rZbSdd+9aX4KLnh5IQKDY7pUTK2pTpwmxZcrRDLZE0juCA7cdUDvWSBruO7EFxVYw23FumX2e8agJz4zXrt7YCpo5zQrGHLAcEL1lBWmciGBVcgYJEjTlnggHTCCenTsyzJ0vU42XbFcf9WvqSIdiUTLrvl2uWzpT8hGdPKuwfcREcPXzDpLgq9awQgxDBFwIDAQABAoIBAHw19jnmPLqYA8TZe7q+xQyh+4FdIOaJLsPRe2L9IwJ8xC41CjTRLssmbSRCuloFQo2F/G78kn7MwLerj3YGaHmKNmfSBFaOCk3OOwDtO6EXbTmXGqzWkeYh+h5HTatqjqZUS6Z5YUrjW+IpwyDZS1s51c5yEnnB5DlxA1eb1ADuVpqhdZKKHuSi/MSMhhpKthjjP7E7y5etytlOIQ1aJMTpstoFo8JFPsUelteP6eKI/Ddj3M/N/d/OXPCWqIc+XYXKAki24TnwZEvaaDOKPbwi7UqaNMppSxDQd3Ba8peqOObulOUD2WKYuVUo1gBrQoBi01LyYjR8ylzVP/686ZECgYEA/yUGkXL09+iWnkZ0T30itHWGtNL5VpoT8vVvEENrrn0tAZ9Id0ICvEWxtRwSZKCJ45jvasxI57oifs3980hGdiwnICYphR5INoZGnCr/76M5BUV21p7E+9Q8TQ+vT3XJrmCDBfs5FmMabirblka7JFA4ImuGz+yp0q5z08F9LOkCgYEAw/KkYdpzK3wwSVQ5QOop68UOJ2PvjH3Ixn75unzqT2Ur0pzJ5EFop5DJOAyA4I1mF+PKVH3JM4tkHswuCmv5QAgDSego/CgKMTvO8NZ4apKO37Y3rhkbywscBUno5aKv8IKH6fsqkgTW+zdkMBRmttknnVXXST9hQduJDjvRvf8CgYAxKJ4aWg7O0RZsCmEQi6irIlXA80EtKWSclCNA++x8YwvP2zOoHqTOR5NUtMLqdm/61RWT+yY0140b5259eDhzPlCPhirLxijdsINcRYCoWEd9N4QNF+wWJS81HceGRMiF+3xjI6M9J/0IquNCEgCun3IhV5xS5WNW/1u7ufT3OQKBgQClSiIWFUL/lJPa13wwYRcZtjE5Uxi4R0a9AlFAWa0BalNsJyw7Kl3Qs9a5O/re8QgE8Pc1DKJTo6rCO/Q+gFRSCjBUAM3J3zCx1US8bf5Hz/dLiRcw+icywN1TLMSUKQcG9+UUn/WKFSEP6urNj/gRF99N3iJrvFYbSsgzRIyqGwKBgQD44nfARY0o8Yz5DEhc5MSPDcSumUetfYYbALWx5dEF9dE1dJcRw9w/+8hQMg5cRXfpxBZVvYI8cgnoFHJJUL3pR6LBeK3g0kfwuUPBcDBQW43SMqCTSAYLewvUZyXCgpr7y2vkJzDZm/vVa9ReTm+1XPxadq4TGwjZH4Q6AR6HZA==',
//            "rsaPublicKey"=>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAw0sI1j8EP7rPoMONRsXLfFfGx+molBlPtKHDwP2P0LuhN3W+Ndum03yxzaaCK2TkNs+V52DeP4MA2zUZ3BVKMj4RDJx9HrqMzRufq4rSY200ojBvKZelhsB9yAjFFo8462m9Wy2jAXKAoGdoZHRY+4rZbSdd+9aX4KLnh5IQKDY7pUTK2pTpwmxZcrRDLZE0juCA7cdUDvWSBruO7EFxVYw23FumX2e8agJz4zXrt7YCpo5zQrGHLAcEL1lBWmciGBVcgYJEjTlnggHTCCenTsyzJ0vU42XbFcf9WvqSIdiUTLrvl2uWzpT8hGdPKuwfcREcPXzDpLgq9awQgxDBFwIDAQAB'
//        ];
//
        $config = array (
            //签名方式,默认为RSA2(RSA2048)
            'sign_type' => "RSA2",

            //支付宝公钥
            'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAw0sI1j8EP7rPoMONRsXLfFfGx+molBlPtKHDwP2P0LuhN3W+Ndum03yxzaaCK2TkNs+V52DeP4MA2zUZ3BVKMj4RDJx9HrqMzRufq4rSY200ojBvKZelhsB9yAjFFo8462m9Wy2jAXKAoGdoZHRY+4rZbSdd+9aX4KLnh5IQKDY7pUTK2pTpwmxZcrRDLZE0juCA7cdUDvWSBruO7EFxVYw23FumX2e8agJz4zXrt7YCpo5zQrGHLAcEL1lBWmciGBVcgYJEjTlnggHTCCenTsyzJ0vU42XbFcf9WvqSIdiUTLrvl2uWzpT8hGdPKuwfcREcPXzDpLgq9awQgxDBFwIDAQAB",

            //商户私钥
            'merchant_private_key' => "MIIEpAIBAAKCAQEAw0sI1j8EP7rPoMONRsXLfFfGx+molBlPtKHDwP2P0LuhN3W+Ndum03yxzaaCK2TkNs+V52DeP4MA2zUZ3BVKMj4RDJx9HrqMzRufq4rSY200ojBvKZelhsB9yAjFFo8462m9Wy2jAXKAoGdoZHRY+4rZbSdd+9aX4KLnh5IQKDY7pUTK2pTpwmxZcrRDLZE0juCA7cdUDvWSBruO7EFxVYw23FumX2e8agJz4zXrt7YCpo5zQrGHLAcEL1lBWmciGBVcgYJEjTlnggHTCCenTsyzJ0vU42XbFcf9WvqSIdiUTLrvl2uWzpT8hGdPKuwfcREcPXzDpLgq9awQgxDBFwIDAQABAoIBAHw19jnmPLqYA8TZe7q+xQyh+4FdIOaJLsPRe2L9IwJ8xC41CjTRLssmbSRCuloFQo2F/G78kn7MwLerj3YGaHmKNmfSBFaOCk3OOwDtO6EXbTmXGqzWkeYh+h5HTatqjqZUS6Z5YUrjW+IpwyDZS1s51c5yEnnB5DlxA1eb1ADuVpqhdZKKHuSi/MSMhhpKthjjP7E7y5etytlOIQ1aJMTpstoFo8JFPsUelteP6eKI/Ddj3M/N/d/OXPCWqIc+XYXKAki24TnwZEvaaDOKPbwi7UqaNMppSxDQd3Ba8peqOObulOUD2WKYuVUo1gBrQoBi01LyYjR8ylzVP/686ZECgYEA/yUGkXL09+iWnkZ0T30itHWGtNL5VpoT8vVvEENrrn0tAZ9Id0ICvEWxtRwSZKCJ45jvasxI57oifs3980hGdiwnICYphR5INoZGnCr/76M5BUV21p7E+9Q8TQ+vT3XJrmCDBfs5FmMabirblka7JFA4ImuGz+yp0q5z08F9LOkCgYEAw/KkYdpzK3wwSVQ5QOop68UOJ2PvjH3Ixn75unzqT2Ur0pzJ5EFop5DJOAyA4I1mF+PKVH3JM4tkHswuCmv5QAgDSego/CgKMTvO8NZ4apKO37Y3rhkbywscBUno5aKv8IKH6fsqkgTW+zdkMBRmttknnVXXST9hQduJDjvRvf8CgYAxKJ4aWg7O0RZsCmEQi6irIlXA80EtKWSclCNA++x8YwvP2zOoHqTOR5NUtMLqdm/61RWT+yY0140b5259eDhzPlCPhirLxijdsINcRYCoWEd9N4QNF+wWJS81HceGRMiF+3xjI6M9J/0IquNCEgCun3IhV5xS5WNW/1u7ufT3OQKBgQClSiIWFUL/lJPa13wwYRcZtjE5Uxi4R0a9AlFAWa0BalNsJyw7Kl3Qs9a5O/re8QgE8Pc1DKJTo6rCO/Q+gFRSCjBUAM3J3zCx1US8bf5Hz/dLiRcw+icywN1TLMSUKQcG9+UUn/WKFSEP6urNj/gRF99N3iJrvFYbSsgzRIyqGwKBgQD44nfARY0o8Yz5DEhc5MSPDcSumUetfYYbALWx5dEF9dE1dJcRw9w/+8hQMg5cRXfpxBZVvYI8cgnoFHJJUL3pR6LBeK3g0kfwuUPBcDBQW43SMqCTSAYLewvUZyXCgpr7y2vkJzDZm/vVa9ReTm+1XPxadq4TGwjZH4Q6AR6HZA==",

            //编码格式
            'charset' => "UTF-8",

            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

            //应用ID
            'app_id' => "2018042002585934",

            //异步通知地址,只有扫码支付预下单可用
            'notify_url' => "http://www.baidu.com",

            //最大查询重试次数
            'MaxQueryRetry' => "10",

            //查询间隔
            'QueryDuration' => "3"
        );
            // 调用barPay方法获取当面付应答
            $barPay = new AlipayTradeService($config);
            $barPayResult = $barPay->barPay($barPayRequestBuilder);

            switch ($barPayResult->getTradeStatus()) {
                case "SUCCESS":
                    echo "支付宝支付成功:" . "<br>--------------------------<br>";
                    print_r($barPayResult->getResponse());
                    break;
                case "FAILED":
                    echo "支付宝支付失败!!!" . "<br>--------------------------<br>";
                    if (!empty($barPayResult->getResponse())) {
                        print_r($barPayResult->getResponse());
                    }
                    break;
                case "UNKNOWN":
                    echo "系统异常，订单状态未知!!!" . "<br>--------------------------<br>";
                    if (!empty($barPayResult->getResponse())) {
                        print_r($barPayResult->getResponse());
                    }
                    break;
                default:
                    echo "不支持的交易状态，交易返回异常!!!";
                    break;
            }
            return;
    }
}