<?php
namespace app\wxshop\controller;

use think\Controller;
use think\Db;
use think\Query;
use think\Request;
use app\wxshop\controller\Token;
use app\wxshop\model\item\SpecsGoodsPriceModel;
use app\wxshop\model\item\SpecsModel;

/**
 * 申请退款退货
 * Class Refund
 * @package app\wxshop\controller
 */
class Refund extends Token
{
    //申请退款退货
    public function apply(){
        $res = $this->request->post();
        $user_id  = self::getUserId();

        if(!isset($res['goods_id'])){
            return json(['code'=>"-3","msg"=>"goods_id参数错误","data"=>""]);
        }
        //验证订单号
        if(!isset($res['order_id']) || empty($res['order_id'])){
            return json(['code'=>"-3","msg"=>"参数错误","data"=>""]);
        }

    //        退单类型 1为退货  2 为退款  3 换货
     //验证退单类型
        if(!isset($res['type']) || empty($res['type'])){
            return json(['code'=>'-3','msg'=>"参数错误",'data'=>'']);
        }
        //原因
        if(!isset($res['reason']) || empty($res['reason'])){
            return json(['code'=>"-3","msg"=>"参数错误","data"=>""]);
        }

        $type = intval($res['type']);
        if($type !==1 && $type!==2&& $type!==3){
            return json(['code'=>"-3","msg"=>"参数错误","data"=>""]);
        }

        // 1 判断该订单是否可以退货
        $order = DB::name("order")->where("id",intval($res['order_id']))->find();

//        退单状态	0:正常	1退款中	2 退款成功 3 退款关闭 4 待寄件
        if($order == false){
            return json(['code'=>"100","msg"=>'该订单不存在，请核对！',"data"=>""]);
        }
        if($order['pay_status']== 0){
            return json(['code'=>"100","msg"=>'该订单未支付，不允许退货退款！',"data"=>""]);
        }
        if($user_id !==$order['member_id']){
            return json(["code"=>"-3","msg"=>"订单错误","data"=>""]);
        }

        //2 判断该对应的 商品 是否存在  是否可以退
        $order_goods = DB::name("order_goods")->where("id",intval($res['goods_id']))->find();
        if($order_goods == false){
            return json(['code'=>"100","msg"=>'该订单对应的商品不存在，请核对！',"data"=>""]);
        }

        //查看是否正在申请中
        $order_refund_apply = Db::name('order_refund_apply')->where('order_id',intval($res['order_id']))
            ->where('goods_id',intval($res['goods_id']))->find();
        if( $order_refund_apply['status'] == 1 || $order_refund_apply['status'] == 2 ){
            return json(['code'=>"100","msg"=>'已经申请过啦！！',"data"=>""]);
        }

        //判断是否为跨境购商品,跨境购商品不允许线上退换货,只能人工客户
        $item_mold_id = Db::name('item')->where('id',$order_goods['item_id'])->value('mold_id');
        if( $item_mold_id == 1 ){
            return json(['code'=>"100","msg"=>'跨境购商品售后请直接联系人工客服',"data"=>""]);
        }

        $pic = input('pic','');
        $remarks = input('remarks','');

        $sn = 'td'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),
                1))),0,8);
        //退单信息
        $apply = [
            "order_id"=>intval($res['order_id']),
            "goods_id"=>intval($res['goods_id']),
            "remarks" =>$res['remarks'],
            "reason" =>$res['reason'],
            "type"=>$type,
            "pic"=>$pic,
            "status"=>1,
            "sn"=>$sn,
            "add_time"=>time(),
        ];
        //订单更改状态信息
        try{
            $update_order = [
                "refund_status"=>1,
                "refund_type"=>1
            ];

            $update_order_goods = [
                "refund_status"=>1,
            ];
        Db::startTrans();

        //添加退单 单表
        //添加申请退单数据
            $res2 = db::name("order_refund_apply")->insert($apply);
            if($res2 == false){
                return json(['code'=>100,'msg'=>'服务器内部错误',"data"=>"添加失败！"]);
            }
            //  更改对应的订单状态
            db::name("order")->where("id",intval($res['order_id']))->update($update_order);
            //更改对应的订单 明细 状态
            db::name("order_goods")->where("id",intval($res['goods_id']))->update($update_order_goods);
            Db::commit();
            return json(['code'=>200,"msg"=>"申请成功，请等待审核","data"=>'']);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            // 回滚事务
            Db::rollback();
            return json(['code'=>100,'msg'=>'服务器内部错误',"data"=>$error]);
        }
    }

    // 退款退单详情
    public function applyDetails(){

        $order_id = input('order_id');
        $goods_id = input('goods_id');

        $order_refund_apply = \db('order_refund_apply')
            ->field('sn,remarks,reason,pic,type,add_time,status,a_remarks,handle_time,money')
            ->where('order_id',$order_id)
            ->where('goods_id',$goods_id)
            ->find();

        $order_refund_apply['add_time'] = date('Y-m-d H:i:s',$order_refund_apply['add_time'] );
        $order_refund_apply['handle_time'] = date('Y-m-d H:i:s',$order_refund_apply['handle_time'] );

        if($order_refund_apply == false){
            return json(['code'=>-1,'msg'=>'对不起，你还未退单信息！',"data"=>'']);
        }
        return json(['code'=>200,'msg'=>'查询成功！',"data"=>$order_refund_apply]);

    }
}