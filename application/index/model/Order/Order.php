<?php
/*
    订单模型
*/
namespace app\index\model\Order;


use think\Model;
use think\Cache;
use think\Db;

class Order extends Model
{
    protected $table = 'ddxm_order';
    public function order_list($shop_id,$data){
        $where['shop_id'] = intval($shop_id);
        $where['type'] = !isset($data['type'])?1:intval($data['type']);
        return $this->alias("a")->where($where)
                ->field("a.sn,a.shop_id as shop,a.member_id,a.old_amount,a.amount,a.pay_way,a.overtime as time,a.waiter,a.waiter_id,a.order_status as order_list_status,a.id,m.mobile,a.overtime,is_outsourcing_goods")
                ->join("ddxm_member m",' a.member_id = m.id','LEFT')
                ->order("create_time","desc");
    }
    public function getPayWayAttr($value){
        if (empty($value)) {
            return '未支付';
        }
        $status = [
            1 => '微信',
            2 => '支付宝',
            3 => '余额',
            4 => '银行卡',
            5 => '现金支付',
            6 => '美团',
            7 => '赠送',
            8 => '门店自用',
            9 => '兑换',
            10 => '包月服务',
            11 => '定制疗程',
            12 => '超级汇购',
            14 => '云客赞',
            99 => '异常充值'
        ];
        return $status[$value];
    }
    public function getTimeAttr($value){
        if($value){
            return date("Y-m-d H:i:s",$value);
        }
        return "暂无";
    }
     public function getOrderStatusAttr($value)
    {
        $status = [
            0 =>'待发货',
            1 => '待收货',
            2 => '确认收货',
            -1 => '申请退款',
            -2 => '退货退款',
            -7 => '已取消',
            8 => '配送中',
            9 => '待处理'
        ];
        return $status[$value];
    }
    public function getOrderListStatusAttr($value){
        $status = [
            2=>'正常',
            -3=>'有退单',
            -6=>'已退单',
        ];
        return $status[$value];
    }
    public function getShopAttr($value){    
        return db::name("shop")->where("id",$value)->value("name");
    }

    public function getwaiter($id){
        $data = db::name("shop_worker")
            ->alias("a")
            ->where("a.id",intval($id))
            ->field("a.name,a.mobile,t.name as shop,a.addtime,a.post_id")
            ->join("ddxm_shop t",'t.id = a.sid')
            ->find();
        $data['type'] ="职位错误";
        if($data['post_id'] >0){
            $data ['type'] = Db::name("shop_post")->where("id",$data['post_id'])->value("title");
        }
        $data['addtime'] = $data['addtime']?date("Y-m-d H:i:s",$data['addtime']):"未录入";
        return $data;
    }
   /* public function getService($val){
        $data = db::name("service")->where("id","in",$val)->field("sname")->select();
        dump($data);
        foreach ($data as $val) {
            $val = join(",",$val);
            $temp_array[] = $val;
        }
        dump($temp_array);
        return implode(",", $temp_array);
    }*/
    public function orderDetails($val){
        $data = $this->where("id",intval($val['id']))
                ->field("id,sn,overtime as time,shop_id as shop_code,is_online,waiter,old_amount,amount,member_id,pay_way")
                ->find();
        if($data){
            $data['member'] = empty($data['member_id'])?"匿名用户":$this->getmember($data['member_id']);
        }else{
            $data = "暂无数据";
        }
        return $data;
    }
    public function getShopCodeAttr($value){
        $data = DB::name("shop")->where("id",$value)->field("code,name")->find();
        if($data['code']){
            $data = $data['code']."-".$data['name'];
        }else{
            $data = $data['name'];
        }
        return $data;
    }
    public function getIsOnlineAttr($val){
        if($val===0){
            return "门店收银";
        }else if($val===1){
            return "线上支付";
        }
    }
    public function getmember($id){
       
        $data = db::name("member")->alias("a")
            ->where("a.id",$id)->field("a.id,a.nickname,a.shop_code,a.mobile,a.regtime,a.level_id")
            ->find();
        $level = DB::name("member_level")->where("id",$data['level_id'])->value("level_name");
        $money  = Db::name("member_money")->where("member_id",$data['id'])->value("money");
        if($money){
            $data['money'] = $money;
        }else{
             $data['money'] = 0;
        }
        if($level){
            $data['level_name'] = $level;
        }else{
            $data['level_name'] = "错误数据";
        }
        if($data['regtime']){
            $data['regtime'] = date("Y-m-d H:i:s",$data['regtime']);
        }else{
            $data['regtime'] = '未录入';
        }
        $data['name'] = db::name("shop")->where("code",$data['shop_code'])->value("name");
        return $data;
    }
    public function getLevel($val){
        $data = Db::name("level")->where("id",$val)->value("level_name");
        return $data;
    }

    //获取充值时的店铺会员等级对应的累积金额
    public function getLevelStandard($shop_id){
        $data = Db::name("shop")->where("id",$shop_id)->value("level_standard");
        return json_decode($data,true);
    }
    public function getRefundDetails($data){
        foreach($data as $k=>$v){
            $data[$k]['total'] = $v['num'] *$v['real_price'];
            $data[$k]['refund_num'] = $v['num'] - $v['refund'];
            $data[$k]['refund_old_num'] = $data[$k]['refund_num'];
            $data[$k]['refund_price'] = $v['real_price'];
        }
        return $data;
    }
    
}
