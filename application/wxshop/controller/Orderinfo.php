<?php
namespace app\wxshop\controller;

use app\wxshop\controller\Token;
use app\wxshop\model\order\OrderinfoModel;
use app\wxshop\model\assemble\AssembleListModel;
use think\Controller;
use think\Db;
use think\Query;
use think\Request;
/**
提交订单
 */
class Orderinfo extends Token
{
    /**
     *status :1待付款，2待发货，3待收货，4待评价，5已完成，6已经取消订单
     * 订单列表
     */
    public function getAllList(){
        $memberId = self::getUserId();
        $data = $this ->request ->param();
        if( !empty($data['page']) && !empty($data['limit']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $where = [];
        if( $data['status'] == 1 ){
            $where[] = ['pay_status','eq',0];
        }
        if( $data['status'] == 2 ){
            $where[] = ['order_status','eq',0];
            $where[] = ['pay_status','neq',0];
        }
        if( $data['status'] == 3 ){
            $where[] = ['order_status','eq',1];
            $where[] = ['pay_status','neq',0];
        }
        if( $data['status'] == 4 ){
            $where[] = ['evaluate','eq',0];
            $where[] = ['pay_status','neq',0];
            $where[] = ['order_status','eq',2];
        }
        if( $data['status'] == 5 ){
            //售后，表示有退单的订单列表
            $where[] = ['refund_status','neq',0];
        }
        $where[] = ['member_id','eq',$memberId];
        $where[] = ['is_online','eq',1];
        $where[] = ['isdel','eq',0];
        $where[] = ['type','neq',3];
        $Order = new OrderinfoModel();
        $field = 'id,member_id,sn,amount,number,evaluate,pay_status,order_status,order_distinguish,refund_status,refund_type';
        $orderlist = $Order ->where($where)->page($page)
            ->order('id desc')
            ->field($field)
            ->select()->append(['status','item_list']);
        return json(['code'=>200,'msg'=>'获取成功','data'=>$orderlist]);
    }

    /***
     * 1待付款，2待发货，3待收货，4待评价,5售后
     * 给user控制器调用的方法，计算有多少订单
     */
    public function countOrder($status){
        $memberId = self::getUserId();
        $data = $this ->request ->param();
        $where = [];
        if( $status == 1 ){
            $where[] = ['pay_status','eq',0];
        }
        if( $status == 2 ){
            $where[] = ['order_status','eq',0];
            $where[] = ['pay_status','eq',1];
        }
        if( $status == 3 ){
            $where[] = ['order_status','eq',1];
            $where[] = ['pay_status','eq',1];
        }
        if( $status == 4 ){
            $where[] = ['evaluate','eq',0];
            $where[] = ['pay_status','neq',0];
            $where[] = ['order_status','eq',2];
        }
        if( $status == 5 ){
            //售后，表示有退单的订单列表
            $where[] = ['refund_status','neq',0];
        }
        $where[] = ['member_id','eq',$memberId];
        $where[] = ['is_online','eq',1];
        $where[] = ['isdel','eq',0];
        $where[] = ['type','neq',3];
        $Order = new OrderinfoModel();
        $field = 'id,member_id,sn,amount,number,evaluate,pay_status,order_status,order_distinguish';
        $count = $Order ->where($where)
            ->order('id desc')
            ->field($field)
            ->count();
        return $count;
    }

    /***
     * 拼团订单详情
     */
    public function assembleorderInfo(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>404,'msg'=>'请选择订单']);
        }
        $Order = new OrderinfoModel();
        $order = $Order ->where('id',$data['id'])->find();
        if( !$order || ($order['order_distinguish'] !=1) ){
            return json(['code'=>404,'msg'=>'订单信息错误或此订单不是拼团订单']);
        }
        $assembleListId = Db::name('assemble_info')->where(['order_id'=>$data['id'],'o_sn'=>$order['sn']])->value('assemble_list_id');
        if( !$assembleListId ){
            return json(['code'=>404,'msg'=>'订单信息错误请联系客服']);
        }

        $AssembleList = new AssembleListModel();
        $list = $AssembleList->where('id',$assembleListId)
            ->field('id,assemble_id,create_time,end_time,num,r_num,status,reason,assemble_price,old_price,price,over_time')
            ->find()->append(['info']);
        $info = [];
        $memberId = self::getUserId();
        foreach ($list['info'] as $k=>$v){
            if( $memberId == $v['member_id'] ){
                if( $v['commander'] == 1 ){
                    $info['price'] = $list['assemble_price'];
                }else{
                    $info['price'] = $list['price'];
                }
            }
        }

        //做数据回显
        $info['tuanyuan_price'] = $list['price'];       //普通团员价
        $info['item_id'] = $list['info']['0']['item_id'];
        $info['mold_id'] = $list['info']['0']['mold_id'];
        $mold = Db::name('item_type')->where('id',$info['mold_id'])->value('title');
        $info['mold'] = $mold?$mold:'熊猫自营';
        $info['update'] = $order['assemble_update'];
        $info['assemble_id'] = $order['event_id'];
        $info['assemble_list_id'] = $assembleListId;
        //查询限购
        $map = [];
        $map[] = ['assemble_id','eq',$order['event_id']];
        $map[] = ['update','eq',$order['assemble_update']];

        $attr = Db::name('assemble_attr') ->where($map)->field('buy_num,all_stock,remaining_stock') ->find();
        if( $attr ){
            $info['buy_num'] = $attr['buy_num'];
            if( $attr['all_stock'] == 0 ){
                //不限购
                $info['remaining_stock'] = -1;      //表示不限制
            }else{
                $info['remaining_stock'] = $attr['remaining_stock'];
            }
        }
        //做数据回显

        $info['end_time'] = $list['end_time'];
        $info['reason'] = $list['reason'];
        $info['status'] = $list['status'];
        $info['old_price'] = $list['old_price'];
        $info['num'] = $list['num'];
        $info['r_num'] = $list['r_num'];
        $info['item_name'] = $list['info']['0']['item_name'];

        $info['item_pic'] = "http://picture.ddxm661.com/".$list['info']['0']['item_pic'];
        $info['time'] = time();
        $info['info'] = $list['info'];
        return json(['code'=>200,'msg'=>'获取成功','data'=>$info]);
    }
}