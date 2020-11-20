<?php
/*
    订单模型
*/
namespace app\wxshop\model\order;
use think\Model;
use think\Cache;
use think\Db;

/***
 * 订单信息列表专用  order模型
 * Class OrderinfoModel
 * @package app\wxshop\model\order
 */
class OrderinfoModel extends Model
{
    protected $table = 'ddxm_order';

    /***
     *获取订单的商品
     */
    public function getItemListAttr($val,$data){
        $orderId = $data['id'];
        $item = Db::name('order_goods')
            ->alias('a')
            ->where('a.order_id',$orderId)
            ->join('item b','a.item_id=b.id')
            ->field('a.id,a.pic,a.item_id,a.subtitle,a.num,a.real_price,a.attr_name,b.mold_id,refund_status,a.status')
            ->select();
        $moldId = [];
        foreach ($item as $k=>$v){
            $item[$k]['mold'] = '';
            $item[$k]['pic'] = config('QINIU_URL').$v['pic'];
            array_push($moldId,$v['mold_id']);
        }
        $where = [];
        $where[] = ['id','in',implode(',',$moldId)];
        $mold = Db::name('item_type')->where($where)->field('id,title')->select();
        foreach ( $item as $kk=>$vv ){
            foreach ( $mold as $k=>$v ) {
                if( $vv['mold_id'] == $v['id'] ){
                    $item[$kk]['mold'] = $v['title'];
                }
            }
        }
        //退单状态
        $refundStatus = [
            7   =>'申请退款中',
            8   =>'已退款',
            9   =>'退款拒绝',
            10   =>'退款已关闭',
            11   =>'退款已取消',
            12   =>'退货寄件中',
        ];
        //判断状态
        foreach ( $item as $k=>$v ){
            $refund_type = '';
            if( ($v['status'] == 2) || ($v['refund_status'] !=0) ){
                if( $v['refund_status'] == 1 ){
                    $refund_type = $refundStatus[7];
                }else if( $v['refund_status'] == 2 ){
                    $refund_type = $refundStatus[8];
                }else if( $v['refund_status'] == 3 ){
                    $refund_type = $refundStatus[10];
                }else if( $v['refund_status'] == 4 ){
                    $refund_type = $refundStatus[12];
                }else if($v['refund_status'] == 5){
                    $refund_type = $refundStatus[9];
                }else{
                    $refund_type =  '退款中';
                }
            }else{
                $refund_type = '';  //没有退单
            }
            $item[$k]['refund_type'] = $refund_type;
        }
        return $item;
    }

    /***
     * 计算订单的状态
     */
//    public function getStatusAttr($val,$data){
//        $evaluate = $data['evaluate'];      //待评价
//        $pay_status = $data['pay_status'];  //支付状态0=待付款，1= 付款，-1= 取消订单
//        $order_status = $data['order_status'];  //0=待发货,1=待收货,2已完成,-1=申请退货,-2=退货完成,-5=申请退款,-6=退款完成,-7=门店取消,-8= 已取消,8=配送中,9=待处理'
//        $refund_status = $data['refund_status'];//0:正常 1退款中 2 退款成功 3 退款关闭 4 待寄件 5 退款拒绝 6 退款取消（用户手动取消退款） 7 退货寄件中'、
//        $refund_type = $data['refund_type'];//0为正常  1为有退货
//        //订单状态
//        $status = [];   //最终状态
//        $status = [
//            1   =>'待付款',
//            2   =>'待发货',
//            3   =>'待收货',
//            4   =>'已完成',
//            5   =>'已取消',
//            6   =>'待评价',
//        ];
//        //退单状态
//        $refundStatus = [
//            7   =>'申请退款中',
//            8   =>'已退款',
//            9   =>'退款拒绝',
//            10   =>'退款已关闭',
//            11   =>'退款已取消',
//            12   =>'退货寄件中',
//        ];
//        $orderType = 0;     //订单状态
//        $refundType = 0;    //退单状态
//
//        //订单状态
//        if( $pay_status == 0 ){
//            $orderType = 1; //代付款
//        }else if( $pay_status == -1 ){
//            $orderType = 5; //已取消
//        }else{
//            //已付款
//            if( $order_status == 0 ){
//                $orderType = 2; //代发货
//            }else if( $order_status == 1 && $evaluate == 0 ){
//                $orderType = 3; //待收货
//            }else if( $order_status == 2 && $evaluate == 0 ){
//                $orderType = 6; //已收货待评价
//            }else{
//                $orderType = 4; //已收货已评价，则订单完成
//            }
//        }
//        //判断订单状态
//        if( $refund_status != 0 || $refund_type==1 ){
//            //有退单
//            if( $refund_status == 1 ){
//                $refundType = 7;
//            }else if( $refund_status == 2 ){
//                $refundType = 8;
//                $orderType = 4;
//            }else if( $refund_status == 3 ){
//                $refundType = 10;
//                $orderType = 4;
//            }else if( $refund_status == 4 ){
//                $refundType = 12;
//            }else if( $refund_status == 5 ){
//                $refundType = 9;
//                $orderType = 4;
//            }else if( $refund_status == 6 ){
//                $refundType = 11;
//                $orderType = 4;
//            }
//        }
//        $orderStatus = [];  //返回的订单状态
//        $orderStatus = [
//            'order_type'    =>$status[$orderType],
//            'refund_type'   =>$refundType==0?'':$refundStatus[$refundType]
//        ];
//        return $orderStatus;
//    }
    public function getStatusAttr( $val,$data ){
        $pay_status = $data['pay_status'];  //支付状态
        $order_status = $data['order_status'];  //订单状态
        $refund_status = $data['refund_status'];    //退货状态
        $evaluate = $data['evaluate'];      //待评价
        $status = [
            1     =>'待支付',
            2     =>'待发货',
            3     =>'待收货',
            9     =>'待评论',
            4     =>'已完成',
            5     =>'申请退款中',
            6     =>'退款成功',
            7     =>'退款拒绝',
            8     =>'已取消'
        ];
        if( $pay_status == 0 ){
            $myStatus = 1;
        }else if( $pay_status == -1 ){
            $myStatus = 8;
        }else if( $refund_status == 2 ){
            $myStatus = 4;
        }else if( $order_status == 0 ){
            $myStatus = 2;
        }else if( $order_status == 1 ){
            $myStatus = 3;
        }else if( $evaluate==0 ){
            $myStatus = 9;
        }else{
            $myStatus = 4;
        }
        return  ['order_type'=>$status[$myStatus],'refund_type'=>''];
    }

    //获取用户昵称
    public function getMemberNameAttr($val,$data) {
        $memberId = $data['member_id'];
        if( empty($memberId) ){
            return '';
        }
        $member =Db::name('member')->where('id',$memberId)->field('nickname,wechat_nickname')->find();
        if( !empty($member['wechat_nickname']) ){
            return $member['wechat_nickname'];
        }
        return $member['nickname'];
    }

    public function getAddTimeAttr($val){
        return date('Y-m-d',$val);
    }
    public function getPaytimeAttr($val){
        if( empty($val) ){
            return '';
        }
        return date('Y-m-d',$val);
    }
}