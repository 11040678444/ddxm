<?php
namespace app\wxshop\controller;

use think\Controller;
use think\Db;
use think\Query;
/**
 * 限时余额延期
 * 充值一笔限时余额为正数，股东数据为负数的限时余额
 */
class DelayExpMoney extends Controller
{
    /***
     * 会员的限时余额延期
     */
    public function DelayExpMoney()
    {
        $data = $this ->request->param();
        if ( empty($data['activate_time']) )
        {
            dump('请输入过期日期（日期格式）');die;
        }
        $where = [];
        if ( !empty($data['id']) )
        {
            $where[] = ['member_id','eq',$data['id']];
            $mobile = Db::name('member')->where('mobile',$data['id'])->value('mobile');
            $memberId = $data['id'];
        }
        if ( !empty($data['mobile']) )
        {
            $memberId = Db::name('member')->where('mobile',$data['mobile'])->value('id');
            $where[] = ['member_id','eq',$memberId];
            $mobile = $data['mobile'];
        }
        $where[] = ['craete_time','>=','1580486400'];     //从2020-02-01 00:00:00 开始
        $list = Db::name('money_expire_log')->where($where)->select();
        if ( count($list) == 0 )
        {
            dump('暂无已过期限时余额');die;
        }
        $dataInfo = [];//需要延长的限时余额

        foreach ( $list as $k=>$v )
        {
            $arr = [];
            $expire = Db::name('member_money_expire') ->where('id',$v['money_expire_id'])->find();
            if( $expire['status'] != 2 )
            {
                continue ;
            }
            $arr = [
                'expire_id' =>$v['money_expire_id'],        //限时余额的ID
                'price'     =>$v['price'],                  //延长的金额
                'member_id'     =>$v['member_id'],          //用户
                'sn'     =>$v['sn'],                        //用来判断股东数据的订单号
                'shop_id'     =>$v['shop_id'],              //门店
            ];
            array_push($dataInfo,$arr);
        }
        if ( count($dataInfo) == 0 )
        {
            dump('暂无已过期限时余额');die;
        }
//        dump($dataInfo);die;
        Db::startTrans();
        try{
            foreach ( $dataInfo as $k=>$v )
            {
                if ( $v['price'] != 0 )
                {
                    //股东数据
                    $arr = [];
                    $arr = [
                        'order_id'  =>0,
                        'shop_id'  =>$v['shop_id'],
                        'order_sn'  =>$v['sn'],
                        'type'  =>4,
                        'data_type'  =>2,
                        'pay_way'  =>3,
                        'price'  =>0-$v['price'],
                        'create_time'  =>time(),
                        'title'  =>'限时余额延时',
                    ];
                    $res = Db::name('statistics_log') ->insert($arr);//股东数据
                    if( !$res )
                    {
                        throw new \Exception('股东数据加入错误');
                    }

                    //会员余额详情
                    $arr = [];
                    $arr = [
                        'member_id'  =>$memberId,
                        'mobile'  =>$mobile,
                        'remarks'  =>'限时余额延时',
                        'reason'  =>'限时余额延时',
                        'addtime'  =>time(),
                        'amount'  =>0-$v['price'],
                        'type'  =>5,
                        'order_id'  =>0
                    ];
                    $res = Db::name('member_details')->insert($arr);//会员余额详情

                    if( !$res )
                    {
                        throw new \Exception('余额详情加入错误');
                    }
                    //添加会员余额
                    $res = Db::name('member_money')->where('member_id',$v['member_id'])->setInc('money',$v['price']);
//                    dump($v['expire_id']);
                    if( !$res )
                    {
                        throw new \Exception('会员余额加入错误');
                    }

                    //将会员的限时余额延长一个月
                    $arr = [];
                    $arr = [
                        'expire_time' =>strtotime($data['activate_time']), //统一2020-05-01 00:00:00过期
                        'status' =>1
                    ];

                    $expire = Db::name('member_money_expire') ->where('id',$v['expire_id'])->find();
                    if ( $expire['status'] == 2 )
                    {
                        $res = Db::name('member_money_expire') ->where('id',$v['expire_id']) ->update($arr);
                    }
                    if( !$res )
                    {
                        throw new \Exception('限时余额延长一个月错误');
                    }

                    //做一张日志表记录延长了哪些限时余额
                    $where = [];
                    $where[] = ['member_id','eq',$v['member_id']];
                    $where[] = ['money_expire_id','eq',$v['expire_id']];
                    $inf = Db::name('money_time_lapse')->where($where)->find();
                    if ( !$inf )
                    {
                        $arr = [];
                        $arr = [
                            'money_expire_id'   =>$v['expire_id'],
                            'member_id'   =>$v['member_id'],
                            'create_time'   =>time(),
                            'use_price'   =>$expire['use_price'],
                        ];
                        $res = Db::name('money_time_lapse')->insert($arr);
                        if( !$res )
                        {
                            throw new \Exception('日志表记录延长了哪些限时余额 错误');
                        }
                    }
                }
            }
            Db::commit();
        }catch ( \Exception $e ){
            Db::rollback();
            dump($e->getMessage());die;
        }
    }
}