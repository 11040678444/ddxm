<?php
/*
    订单模型
*/
namespace app\wxshop\model\order;
use think\Model;
use think\Cache;
use think\Db;

/**
 * 订单
 * Class OrderModel
 * @package app\wxshop\model\order
 */
class OrderModel extends Model
{
	protected $table = 'ddxm_order';
    protected $table_goods = 'ddxm_order_goods';
	public function order_details(){
        return $this->table($this->table)
        ->field("id,sn,realname,old_amount,order_distinguish,detail_address,discount,mobile,amount,postage,discount,add_time,paytime,sendtime,refund_status");
    }
    public function getAddTimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "";
    }
    public function getPaytimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "待支付";
    }
    public function getSendtimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "";
    }
    // 订单 --明细商品表
    public function order_goods($val){
        $data  = $this->table($this->table_goods)->alias("a")
            ->where("order_id",intval($val))
            ->field("a.id,a.subtitle,a.item_id,a.real_price,a.deliver_status,a.deliver_status as deliver,a.num,a.status,a.attr_ids,a.attr_name,i.mold_id,t.title as mold,i.pic,a.express_id,refund_status")
            ->join("item i","a.item_id = i.id")
            ->join("item_type t","i.mold_id =t.id")
            ->select();
        return $data;
    }
    public function getDeliverAttr($val){
        if($val==0){
            return "未发货";
        }else if($val==1){
            return "已发货";
        }
    }
    public function getPicAttr($val){
        $val = explode(",",$val);
        if($val){
            $val = "http://picture.ddxm661.com/".$val[0];
        }else{
            $val = "";
        }
        return $val;
    }
    // 获取 订单 状态
    public function getOrderStatus($val){
        $val = db::name("order")->where("id",$val["id"])->find();
        $status = [];
//        if($val['pay_status'] ==0){
//            $status['status'] = 1;
//            $status['status_attr']=="待付款";
//        }else if($val['pay_status'] == -1){
//            $status['status'] = -1;
//            $status['status_attr'] = "已取消";
//        }else if($val['pay_status'] == 1){
//            if($val["order_status"]  == 0){
//                $status['status'] = 2;
//                $status['status_attr'] = "待发货";
//            }else if($val["order_status"]  == 1){
//                $status['status'] = 3;
//                $status['status_attr'] = "待收货";
//            }else if($val["order_status"]  == 2){
//                $status['status'] = 4;
//                $status['status_attr'] = "待评论";
//                if($val["evaluate"]  == 1){
//                    $status['status'] =5;
//                    $status['status_attr'] = "已完成";
//                }
//            }
//        }
        if($val['pay_status'] ==0){
            $status['status'] = 1;
            $status['status_attr']=="待付款";
        }else if($val['pay_status'] == -1){
            $status['status'] = -1;
            $status['status_attr'] = "已取消";
        }else if($val['pay_status'] == 1){
            if($val["order_status"]  == 0){
                $status['status'] = 2;
                $status['status_attr'] = "待发货";
            }else if($val["order_status"]  == 1){
                $status['status'] = 3;
                $status['status_attr'] = "待收货";
            }else if($val["order_status"]  == 2){
                $status['status'] = 4;
                $status['status_attr'] = "待评论";
                if($val["evaluate"]  == 1){
                    $status['status'] =5;
                    $status['status_attr'] = "已完成";
                }else{
                    $status['status'] =5;
                    $status['status_attr'] = "已完成";
                }
            }else{
                $status['status'] =5;
                $status['status_attr'] = "已完成";
            }
        }else{
            $status['status'] =5;
            $status['status_attr'] = "已完成";
        }
        return $status;
    }
}