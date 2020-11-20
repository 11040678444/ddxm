<?php
namespace app\wxshop\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;
use think\Request;
use app\wxshop\model\order\OrderRetailModel;
use app\wxshop\model\retail\RetailUser;
use app\wxshop\model\retail\RetailFansModel;
/**
商城,用户分销控制器
 */
class Retail extends Token
{
    /***
     *订单列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderList(){
        $data = $this ->request ->param();
        $page = $this ->request ->param('page',1);
        $limit = $this ->request ->param('limit',10);
        $memberId = self::getUserId();
        $where = [];
        if( !empty($data['start_time']) ){
            $where[] = ['create_time','>=',strtotime($data['start_time'].' 00:00:00')];
        }
        if( !empty($data['end_time']) ){
            $where[] = ['create_time','<=',strtotime($data['start_time'].' 23:59:59')];
        }
        if( isset( $data['status'] ) ){
            $where[] = ['status','eq',$data['status']];
        }
        $where[] = ['member_id','eq',$memberId];
        $list = (new OrderRetailModel())
            ->where($where)
            ->page($page,$limit)
            ->field('id,member_id,order_id,price,status,amount,order_goods_id')
            ->order('id desc')
            ->select()
            ->append(['order_info']);
        $count = (new OrderRetailModel()) ->where($where)  ->count();                   //总共多少订单
        $allPrice = (new OrderRetailModel()) ->where($where)  ->sum('price');       //共获得的商品佣金
            return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'allPrice'=>$allPrice,'data'=>$list]);
    }

    /***
     * 客户列表
     */
    public function memberList(){
        $memberId = self::getUserId();
        $data = $this ->request ->param();
        $page = $this ->request ->param('page',1);
        $limit = $this ->request ->param('limit',10);
        $where = [];
        if( !empty($data['start_time']) ){
            $where[] = ['create_time','>=',strtotime($data['start_time'].' 00:00:00')];
        }
        if( !empty($data['end_time']) ){
            $where[] = ['create_time','<=',strtotime($data['end_time'].' 23:59:59')];
        }
        if( empty($data['type']) || ($data['type'] == 1) ){
            $where[] = ['one_member_id','eq',$memberId];        //直属分销员
        }else{
            $where[] = ['two_member_id','eq',$memberId];        //二级分销员
        }
        $list = (new RetailUser())
            ->where($where)
            ->page($page,$limit)
            ->select()->append(['member_info']);
        $res = [];
        if( count($list) > 0 ){
            foreach ( $list as $k=>$v ){
                $arr = [];
                $arr = $v['member_info'];
                if( $v['one_member_id'] == $memberId ){
                    $arr['type'] = 1;
                }
                if( $v['two_member_id'] == $memberId ){
                    $arr['type'] = 2;
                }
                array_push($res,$arr);
            }
        }
        $count = Db::name('retail_user')->where($where)->count();
        return json(['code' =>200,'msg'=>'获取成功','count'=>$count,'data'=>$res]);
    }

    /***
     * 粉丝列表
     */
    public function memberFans(){
        $data = $this ->request ->param();
        $page = $this ->request ->param('page',1);
        $limit = $this ->request ->param('limit',10);
        $where = [];
        if( !empty($data['start_time']) ){
            $where[] = ['create_time','>=',strtotime($data['start_time'].' 00:00:00')];
        }
        if( !empty($data['end_time']) ){
            $where[] = ['create_time','<=',strtotime($data['end_time'].' 23:59:59')];
        }
        $where[] = ['status','eq',!empty($data['type'])?$data['type']:1];
        $where[] = ['member_id','eq',self::getUserId()];
        $list = (new RetailFansModel()) ->where($where)
            ->page($page,$limit)
            ->field('id,status,fans_id')
            ->select()->append(['member_info']);
        $res = [];
        if( count($list) > 0 ){
            foreach ( $list as $k=>$v ){
                $arr = [];
                $arr = $v['member_info'];
                $arr['type'] = $v['status'];
                array_push($res,$arr);
            }
        }
        $count = (new RetailFansModel()) ->where($where)->count();
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$res]);
    }
}
