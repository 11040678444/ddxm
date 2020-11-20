<?php

namespace app\mall\controller;

use think\Db;
use think\Controller;

/**
 * 统计
 * Class TestWx
 * @package app\mall\controller
 */
class Census extends Controller
{
    //统计每日数据
    public function dataCount(){
        $data = $this ->request ->param();
        if( !empty($data['start_time']) ){
            $start_time = strtotime($data['start_time'].' 00:00:00');
        }
        if( !empty($data['end_time']) ){
            $end_time = strtotime($data['end_time'].' 23:59:59');
        }else{
            $end_time = time();
        }
        $allOrderCount = 0; //总订单数
        $allOrderAmount = 0;    //总订单成交额
        $allOrderWeChatAmount = 0;    //总订单微信成交额
        $allOrderYueAmount = 0;    //总订单余额成交额
        $allMember = 0; //总会员
        $allRetail = 0; //总分销员
        $allRefundCount = 0;    //总退单数
        $allRefundAmount = 0;   //总退单金额

        //订单总金额
        $where = [];
        $where[] = ['is_online','eq',1];
        $where[] = ['pay_status ','eq',1];
        $where[] = ['paytime','<=',$end_time];
        if( isset($start_time) ){
            $where[] = ['paytime','>=',$start_time];
        }
        $list = Db::name('order') ->where($where)->field('id,amount,pay_way')->select();
        $allOrderCount = count($list);
        foreach ( $list as $k=>$v ){
            $allOrderAmount += $v['amount'];
            if( $v['pay_way'] == 1 ){
                $allOrderWeChatAmount += $v['amount'];
            }
            if( $v['pay_way'] == 3 ){
                $allOrderYueAmount += $v['amount'];
            }
        }

        //会员
        $memberWhere = [];
        $memberWhere[] = ['status','eq',1];
        $memberWhere[] = ['regtime','<=',$end_time];
        if( isset($start_time) ){
            $memberWhere[] = ['regtime','>=',$start_time];
        }
        $allMember = Db::name('member') ->where($memberWhere)->count();

        //分销员
        $retailWhere = [];
        $retailWhere[] = ['b.status','eq',1];
        $retailWhere[] = ['a.create_time','<=',$end_time];
        if( isset($start_time) ){
            $retailWhere[] = ['a.create_time','>=',$start_time];
        }
        $allRetail = Db::name('retail_user') ->alias('a')
            ->join('member b','a.member_id=b.id')
            ->where($retailWhere)
            ->count();

        //退单
        $refundWhere = [];
        $refundWhere[] = ['status','eq',2];
        $refundWhere[] = ['handle_time','<=',$end_time];
        if( isset($start_time) ){
            $refundWhere[] = ['handle_time','>=',$start_time];
        }
        $refundList = Db::name('order_refund_apply') ->where($refundWhere)->field('id,money')->select();
        $allRefundCount = count($refundList);
        foreach ( $refundList as $k=>$v ){
            $allRefundAmount += $v['money'];
        }
        $res = [
            ['title'=>'会员数','data'=>$allMember],
            ['title'=>'分销员数','data'=>$allRetail],
            ['title'=>'订单数','data'=>$allOrderCount],
            ['title'=>'订单总金额','data'=>$allOrderAmount],
            ['title'=>'订单微信金额','data'=>$allOrderWeChatAmount],
            ['title'=>'订单余额金额','data'=>$allOrderYueAmount],
            ['title'=>'退单数','data'=>$allRefundCount],
            ['title'=>'退单金额','data'=>$allRefundAmount]
        ];
        return json(['code'=>200,'msg'=>'获取成功','data'=>$res]);
    }

    /***
     * 获取分销员的上下级
     */
    public function retailInfo(){
        $data = $this ->request ->param();
        if( empty($data['mobile']) ){
//            return '请输入手机号';
        }
        $memberInfo = Db::name('member') ->where('mobile',$data['mobile']) ->find();
        if( !$memberInfo ){
//            return '未查询到会员';
        }

        $memberInfo['regtime'] = date('Y-m-d H:i:s',$memberInfo['regtime']);
        if( $memberInfo['retail'] == 0 ){
            $memberInfo['retail_info'] = '该用户不是分销员';
        }else{
            $retail_info = Db::name('retail_user')->alias('a')
                ->where('a.member_id',$memberInfo['id'])->find();
            if( $retail_info['one_member_id'] == 0 ){
                $tt = '无';
            }else{
                $tt = Db::name('member')->where('id',$retail_info['one_member_id'])
                    ->field("wechat_nickname as '姓名',mobile as '电话'")
                    ->find();
                $tt['成为分销员时间'] = date('Y-m-d H:i:s',$retail_info['create_time']);
            }
        }
        $res = [];
        $res = [
            ['姓名'=>$memberInfo['nickname']],
            ['微信名'=>$memberInfo['wechat_nickname']],
            ['手机号'=>$memberInfo['mobile']],
//            ['注册时间'=>date('Y-m-d H:i:s',$memberInfo['regtime'])],
            ['是否为分销员'=>$memberInfo['retail'] == 0?'否':'是'],
            ['上级分销员'=>$memberInfo['retail'] == 0?'无':$tt],
        ];

        $this ->assign('res',$res);
        $this ->assign('mobile',$data['mobile']);
        return $this ->fetch();
    }

    public function userRetail(){
        $data = $this ->request ->param();
        if( empty($data['mobile']) ){
            return '请输入手机号';
        }
        $memberInfo = Db::name('member') ->where('mobile',$data['mobile']) ->find();
        if( !$memberInfo ){
            return '未查询到会员';
        }
        if( $memberInfo['retail'] == 1 ){
            //是分销员
            $res = Db::name('retail_user')->where('member_id',$memberInfo['id'])->find();
            if( $res['one_member_id'] == 0 ){
                $result = Db::name('retail_user')->where('member_id',$memberInfo['id'])->setField('one_member_id',1089);
            }
            if( $result ){
                dump('成功');
            }else{
                dump('失败');
            }
        }else{
            $arr = [];
            $arr = [
                'member_id' =>$memberInfo['id'],
                'one_member_id' =>1089,
                'two_member_id' =>0,
                'create_time' =>time(),
                'update_time' =>time(),
                'mobile' =>$memberInfo['mobile'],
                'name' =>$memberInfo['wechat_nickname'],
            ];
            // 启动事务
            Db::startTrans();
            try {
                Db::name('retail_user')->where('member_id',$memberInfo['id'])->delete();
                Db::name('retail_user')->insert($arr);
                Db::name('member')->where('id',$memberInfo['id'])->setField('retail',1);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                dump($e->getMessage());die;
            }
            dump('成功');die;
        }
    }


}