<?php
namespace app\wxshop\model\retail;
use think\Model;
use think\Db;

class RetailUser extends Model
{
    /***
     * 获取用户
     * @param $val
     * @param $data
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMemberInfoAttr($val,$data){
        $memberInfo = Db::name('member') ->where('id',$data['member_id']) ->field('id,nickname as nickname1,wechat_nickname as nickname,pic')->find();
        if( empty($memberInfo['nickname']) ){
            $memberInfo['nickname'] = $memberInfo['nickname1'];
        }
        $where = [];
        $where[] = ['buy_member_id','eq',$data['member_id']];
        $where[] = ['status','neq',2];
        $order = Db::name('order_retail')
            ->where($where)
            ->field('price,amount')
            ->order('id asc')
            ->group('order_id')
            ->select();
        if( count($order) > 0 ){
            $money = 0; //成交金额
            foreach ( $order as $k=>$v ){
                $money += $v['amount'];
                $memberInfo['time'] = date('Y-m-d');   //最近下单时间
            }
            $memberInfo['money'] = $money;   //成交金额
            $memberInfo['count'] = count($order);   //订单数

        }else{
            $memberInfo['money'] = 0;   //成交金额
            $memberInfo['count'] = 0;   //订单数
            $memberInfo['time'] = '无';   //最近下单时间
        }
        return $memberInfo;
    }
}