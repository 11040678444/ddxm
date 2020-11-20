<?php
namespace app\wxshop\controller;

use think\Db;
use think\Exception;
use think\Request;
use app\common\model\WxPayModel;
/**
商城,用户充值
 */
class Cz extends Token
{
    /***
     * 充值
     */
    public function invest_money(){
        $data = $this ->request ->param();
        $rule = '/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
        if (!preg_match($rule,$data['money'])) {
            return json(['code'=>100,'msg'=>'金额不正确']);
        }
        $sn ='CZ'.(strtotime(date('YmdHis', time()))) . substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
        $memberInfo = self::getUserInfo();
        $order = [];    //充值订单信息
        $order = [
            'shop_id'   =>$memberInfo['shop_id'],
            'member_id' =>$memberInfo['id'],
            'type'=>3,
            'number'=>1,
            'discount'=>0,
            'postage'=>0,
            'amount'=>$data['money'],
            'pay_status'=>0,
            'pay_way'=>1,
            'add_time'=>time(),
            'is_online'=>1,
            'order_type'=>1,
            'old_amount'=>$data['money'],
            'user_id'=>$memberInfo['id'],
            'remarks'=>!empty($data['remarks'])?$data['remarks']:'',
            'order_distinguish'=>0
        ];
        // 启动事务
        Db::startTrans();
        try {
            $order['sn'] = $sn.$memberInfo['id'];
            $orderId = Db::name('order')->lock(true)->insertGetId($order);

            //区分充值类型，拼接支付回调传参(0：正常充值、8：充值送活动)【往后类型多时可以修改成switch】
            $type = isset($data['type']) ? 8 : 0;

            //充值
            $WxPay = ['title'=>'捣蛋熊猫充值','order_sn'=>$order['sn'],'amount'=>$data['money'],'openId'=>$memberInfo['openid'],'notify_url'=>config('notify_url').'cz_notify/type/'.$type];

            $res = (new WxPayModel()) ->pay($WxPay);
            if( $res == false ){
                throw new \Exception();
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>100,'msg'=>'服务器繁忙','data'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'充值成功','data'=>$res]);
    }
}