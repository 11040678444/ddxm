<?php

namespace app\mall\controller;

use app\common\controller\Adminbase;
use app\mall\model\recharge\Order;
use think\Db;
/**
 * 活动充值
 */
class Recharge extends Adminbase
{
    /***
     * 活动充值列表
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recharge()
    {
        if ($this->request->isAjax()) {
            $data = $this ->request->param();
            $res = ( new Order() ) ->recharge($data);
            return json($res);
        }
        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);
        return $this ->fetch();
    }

    /***
     * 活动订单充值
     */
    public function rechargeAdd()
    {
        session('codes', mt_rand(1000, 9999));
        return $this->fetch();
    }

    /***
     * 活动充值操作提交
     */
    public function rechargeDoPost()
    {
        $data = $this ->request->param();
        if($data['codes'] != session('codes')){
            return json(['code'=>100,'msg'=>'正在处理您的请求请稍后~']);
        }else{
            session('codes', mt_rand(1000, 9999));
        }
        $res = ( new Order() ) ->rechargeDoPost($data);
        return json(['code'=>1,'msg'=>'充值成功']);
    }

}