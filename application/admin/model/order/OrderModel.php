<?php
namespace app\admin\model\order;
use app\admin\common\Logs;

use app\admin\controller\LogsController;
use app\admin\model\BaseModel;
use app\admin\model\CollectModel;
use app\admin\model\item\ItemCategoryModel;
use app\admin\model\ItemModel;
use app\admin\model\shop\ShopModel;
use app\admin\model\order\OrderGoodsModel;
use app\admin\model\user\IntegralModel;
use app\admin\model\user\UserTicketModel;
use app\admin\model\PayModel;
use think\Db;
use think\Exception;

class OrderModel extends BaseModel
{
    protected $autoWriteTimestamp = false;

    protected $table = 'tf_order';


    //事件 后置操作
    protected static function init()
    {
        self::afterUpdate(function ($orderModel) {
            LogsController::actionLogRecord($orderModel->msg);
            Logs::actionLogRecord($orderModel->msg);
        });
    }

    //获取器(门店代码对应的门店)
    public function getShopIdAttr($value)
    {
        $data = self::getShopList();
        $shopArr = [];
        foreach ($data as $v){
            $shopArr[$v['id']] = $v['name'];
        }
        $shopArr[0] = '捣蛋熊猫总店';
        $shop_name = isset($shopArr[$value])?$shopArr[$value]:'未知门店';
        return $shop_name;
    }
    //修改器
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
        return ['status' => $value,'text' => $status[$value]];
    }

    public function getPayWayAttr($value)
    {
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
            13 => '限时余额',
            14 => '云客赞',
            15  =>'框框宝',
            16  =>'公司转门店',
            99 => '异常充值'
        ];
        return $status[$value];
    }

    public function getSendWayAttr($value)
    {
        if (empty($value)) {
            return '快递';
        }
        $status = [
            1 => '自提',
            2 => '快递',
            3 => '配送',
        ];
        return $status[$value];
    }

    public function getReturntimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i',$value);
        }
        return '暂无';
    }

    public function getCreatetimeAttr($value)
    {
        if ($value) {
            return $value + 3600 * 48;
        }
        return '暂无';
    }

    public function getCanceltimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i',$value);
        }
        return '暂无';
    }

    public function getSendtimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i',$value);
        }
        return '未发货';
    }

    public function getOvertimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i',$value);
        }
        return '未收货';
    }
    //门店信息
    public function getShopAttr($value){
        if($value){
            
            return Db::name("shop")->where("id",$value)->value("name");
        }
        return "暂无信息";
    }

    //订单详情退单状态
    public function getOrderListStatusAttr($value){
        $status = [
            2=>'正常',
            -3=>'有退单',
            -6=>'已退单',
        ];
        return $status[$value];
    }
    //一对一模型
    public function oneOrderGoods()
    {
        return $this->hasOne('OrderGoodsModel','order_id','id')
            ->field('order_id,subtitle')->bind('subtitle');
    }

    //一对一模型 (物流列表)
    public function expressInfo()
    {
        return $this->hasOne('ExpressInfoModel','id','id')
            ->field('id,express_name,express_code');
    }
    
    public function getUserName($table,$id){
        
        if($table=="member"){
           if($id==0){
               $data='匿名用户';
           }else{
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
            }    
        }else if($table=="worker"){
            $data = db::name("shop_worker")
                    ->alias("a")
                    ->where("a.id",intval($id))
                    ->field("a.name,a.mobile,t.name as shop_name,a.addtime,a.post_id")
                    ->join("ddxm_shop t",'t.id = a.sid')
                    ->find();
            $data['type'] ="职位错误";
            if($data['post_id'] >0){
                $data ['type'] = Db::name("shop_post")->where("id",$data['post_id'])->value("title");
            }
            $data['addtime'] = $data['addtime']?date("Y-m-d H:i:s",$data['addtime']):"未录入";
        }
        return $data;
    }
    public function getAddTimeAttr(){

    }
    //一对一模型
    public function oneCoupon()
    {
        return $this->hasOne('app\admin\model\item\ItemCouponModel','id','coupon_id')
            ->field('id,title');
    }
    //本地服务器数据
    public function postOrderListDatas($data){
         foreach ($data as $k => $v) {
           $data[$k]['overtime'] = $this->getOvertimeAttr($v['overtime']);
           $data[$k]['pay_way'] = $this->getPayWayAttr($v['pay_way']);
           $data[$k]['shop_name'] = $this->getShopAttr($v['shop_id']);
           $data[$k]['order_status'] = $this->getOrderListStatusAttr($v['order_status']);
        }
        return $data;
    }
    public function getShopCode($id){
        if($id){
            /*$db  = db::connect(config('ddxx'));*/
            return db::name("shop")->where("id",$id)->value("code");
        }
        return "暂无信息";
    }
    //获取订单详情
    public function getOrderDetails($data){
        $data['overtime'] = $this->getOvertimeAttr($data['overtime']);
        $data['pay_way'] = $this->getPayWayAttr($data['pay_way']);
        $data['shop_name'] = $this->getShopAttr($data['shop_id']);
        $data['shop_code'] = $this->getShopCode($data['shop_id']);
        $data['order_status'] = $this->getOrderListStatusAttr($data['order_status']);
        $data['is_online'] = $data['is_online']==1?'线上商城':'门店收银';
        $data['member'] = $this->getUserName("member",$data['member_id']);
        $data['oprice'] = $this->getGoodsOprice($data['id']);

        return $data;
    }
    public function getGoodsOprice($value){
        $data = Db::name("order_goods")->where("order_id",intval($value))->sum("oprice");
        return $data;

    }
    public function getGoodsList($data){
        foreach ($data as $k => $v) {
            $data[$k]['real_total'] = $v['real_price'] *$v['num'];
        }
        return $data;
    }
    public function getServiceGoodsList($data){
        foreach ($data as $k => $v) {
            $data[$k]['real_total'] = $v['real_price'] *$v['num'];
        }
        return $data;
    }
    //获取
    public function refundList($data){
        foreach ($data as $k => $v) {
            $data[$k]['add_time'] = $this->getCanceltimeAttr($v['create_time']);
            $data[$k]['dealwith_time'] = $this->getCanceltimeAttr($v['dealwith_time']);
        }
        return $data;
    }
    public function refundGoodsList($data,$type){
        foreach($data  as $k=>$v){
            if($type === "1"){
                $data[$k]['r_attr_pic'] = "<img class='layui-upload-img goodsimg' "
                    . "src='http://picture.ddxm661.com/".$v['r_attr_pic']."'>";
            }else if($type==="2"){
                $data[$k]['r_attr_pic'] = "<img class='layui-upload-img goodsimg'"
                    . "src='http://picture.ddxm661.com/".$v['r_attr_pic']."'>";
            }
            $order_id = Db::name("order_refund")->where("r_sn",$v['refund_id'])->value("order_id");
            $order_goods = Db::name("order_goods")->where("order_id",$order_id)->where("item_id",$v['og_id'])->find();
            $data[$k]['num'] = $order_goods['num'];
            $data[$k]['y_num'] = $order_goods['refund'];
            $data[$k]['r_total'] = $v['r_num'] *  $v['r_price'];
        }
        return $data;
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
    //列表数据
    public function getOrderListDatas($where)
    {
        return $this->table($this->table)
            ->alias('a')
            ->field('a.id,a.sn,a.realname,a.shop_id,a.postage,sum(b.num) as number,a.amount,a.addtime,a.send_way,a.order_status,a.pay_status,a.pay_way,c.level_id,c.nickname,d.id as express_id')
            ->join('tf_order_goods b','a.id = b.order_id','LEFT')
            ->join('tf_member c','a.member_id = c.id','LEFT')
            ->join('tf_order_express d','a.id = d.order_id','LEFT')
            ->where(['a.type'=>1,'isDel'=>['neq',1]])
            ->where($where)
            ->group('a.id')
            ->order('a.addtime desc,a.paytime desc');
    }

    //列表数据
    public function getRechargeOrderListDatas($where)
    {
        return $this->table($this->table)
            ->alias('a')
            ->field('a.id,a.member_id,a.sn,a.realname,a.shop_id,a.card_id,a.amount,a.is_online,a.check_status,a.pay_way,a.paytime,a.pay_status,c.nickname,c.mobile')
            ->join('tf_member c','a.member_id = c.id','LEFT')
            ->where(['a.isDel'=>0,'a.type'=>3,'a.pay_status'=>1,'a.card_id'=>0])
            ->where($where)
            ->group('a.id')
            ->order('a.paytime desc');
    }
    //获取总金额
    public function getSumAmount($where){
        return $this->table($this->table)
            ->alias('a')
            ->join('tf_member c','a.member_id = c.id','LEFT')
            ->field('a.mount')
            ->where(['a.isDel'=>0,'a.type'=>3,'a.pay_status'=>1,'a.card_id'=>0])
            ->where($where)
            ->sum('a.amount');
    }
    //获取订单基本信息 type 1 cost_price
    public function getOrderSumCostPrice($order_id,$type =1)
    {
        $db = Db::connect(config('ddxx'));
        $info = $db->name('finance')->where('order_id','=',$order_id)->field('price,cost_price')->find();
        if ($type == 1) {
            return $info['cost_price'] == 0?$info['price']:$info['cost_price'];
        }else{
            return $info['price'];
        }
    }
    //获取订单列表的商品名
    public function getGoodsName($order_id)
    {
        $data = $this->table($this->table)
            ->alias('a')
            ->field('b.subtitle')
            ->join('tf_order_goods b','a.id = b.order_id','LEFT')
            ->where(['a.isDel'=>0,'a.type'=>1])
            ->where(['a.id'=>$order_id])
            ->select();
        $str = '';
        foreach ($data as $v){
            $str.= $v['subtitle']."&emsp;";
        }
        return $str;
    }

    //一对多模型(商品信息)
    public function moreOrderGoods()
    {
        return $this->hasMany('OrderGoodsModel','order_id','id')
            ->field('order_id,ch_id,subtitle,attr_pic,attr_name,num,category_id,price,oprice');
    }

    public function moreOrderExpress()
    {
        return $this->hasOne('OrderExpressModel','order_id','id')
            ->field('order_id,express_sn,express_name');
    }

    //一对一模型(用户信息)
    public function memberInfo()
    {
        return $this->belongsTo('app\admin\model\user\UserModel','member_id','id')
            ->field('id,shop_code,mobile as telephone,nickname');
    }

    //一对一模型(用户信息)
    public function declareInfo()
    {
        return $this->belongsTo('app\admin\model\user\UserDeclareModel','member_id','member_id')
            ->field('member_id,name,idcard,front_idcard,rev_idcard');
    }

    //一对一模型(获取店铺信息)
    public function shopInfo()
    {
        return $this->belongsTo('app\admin\model\shop\ShopModel','shop_id','id')
            ->field('code,name');
    }

    public function getDetailData($id = '')
    {
        $datas = self::with(['memberInfo','shopInfo','moreOrderExpress','oneCoupon'])
            ->table($this->table)->field('fixtime,evaluate,isDel',true)
            ->where('id','=',$id)->find()->toArray();
        $datas['declare_info'] = (new \app\admin\model\user\UserDeclareModel())
            ->where('member_id','=',$datas['member_id'])
            ->field('member_id,name,idcard,front_idcard,rev_idcard')
            ->find();

        //获取商品信息
        $orderGoodList = (new OrderGoodsModel())
            ->field('subtitle,item_id,attr_pic,oprice,price,num,category_id,type_id')
            ->where(['order_id'=>$id])
            ->select();
        // var_dump($orderGoodList);die;
        //获取分类数据(待改进)
        $cateDatas = (new ItemCategoryModel())->getAllCategoryDatas();
//        dump($cateDatas);die;
        $arr = [
            '0' => '未知专区',
            '2' => '国内商品',
            '3' => '跨境商品',
            '9' => '熊猫专区'
        ];
        $itemModle = new ItemModel();
        $db = Db::connect(config('ddxx'));
        foreach ($orderGoodList as &$v){
            $v['cate_name'] = isset($cateDatas[$v['category_id']]['cname'])?$cateDatas[$v['category_id']]['cname']:'暂无分类';
            $suiprice = 0;
            if ($v['type_id'] == 3) {
                $suiprice = $this->lvnums($v['item_id']);
            }
            if ($v['oprice'] == 0){
                $v['oprice'] = $itemModle->where(['id'=>$v['item_id']])->value('cg_standard_price');
            }
            if (!empty($arr[$v['type_id']])) {
                $v['type_id'] = $arr[$v['type_id']];
            }else{
                $type_id = $db->name('item_category')->where(['id'=>$v['type_id']])->value('cate_id');
                if ($type_id == 3) {
                    $suiprice = $this->lvnums($v['item_id']);
                }
                $v['type_id'] = isset($arr[$type_id])?$arr[$type_id]:'未知';
            }
            $v['suiprice'] = $suiprice;
        }
        $datas['more_order_goods'] = $orderGoodList;
        //是否添加了成本
        $whether_exist_cost = $db->name('finance')->where(['order_id'=>$id])->count();
        $datas['whether_exist_cost'] = $whether_exist_cost?1:0;
        //是否存在改价
        $whether_exist_discount = $db->name('discount')->where(['order_id'=>$id])->count();
        $datas['whether_exist_discount'] = $whether_exist_discount?1:0;
        return $datas;
    }

    /**
     * 获取支付时需要的参数
     * @param int $orderid
     */
    public function getPayData($orderid = 0)
    {
        $datas = self::with('oneOrderGoods')
            ->table($this->table)->field('id,sn,amount')
            ->where('id','=',$orderid)->find()->toArray();
        return $datas;
    }
    //获取订单基本信息
    public function getBasicInfo($order_id)
    {
        return $this->where(['id'=>$order_id])
            ->field('amount,addtime,addtime as createtime')
            ->find()
            ->toArray();
    }
    //获取订单基本信息(添加订单成本)
    public function getOrderGoodsLists($order_id)
    {
        $shop_id = $this->where('id','=',$order_id)->value('shop_id');
        $shop_info = model('shop.Shop')->where(['id'=>$shop_id])->field('id,name')->find();
        $list = (new OrderGoodsModel())
            ->where(['order_id'=>$order_id])
            ->field('id,item_id,subtitle,price,num,oprice as cost_price,faker_price')
            ->select();
        foreach ($list as &$v) {
            if (empty($shop_info)) {
                $is_stock = 0;
            }else{
                $is_stock = model('shop.Stock')->getItemStock($v['item_id'],$shop_id);
            }
            $otd_info = model('order.OrderTempData')->where(['order_id'=>$order_id,'item_id'=>$v['item_id']])->field('cost_price,faker_price,shop_id')->find();
            $v['shop_id'] = null;
            $v['cost_price'] = round($v['cost_price'] * $v['num'],2);
            $v['faker_price'] = round($v['faker_price'] * $v['num'],2);
            if ($otd_info) {
                $v['cost_price'] = round($otd_info['cost_price'] * $v['num'],2);
                $v['faker_price'] = round($otd_info['faker_price'] * $v['num'],2);
                $v['shop_id'] = $otd_info['shop_id'];
            }
            $v['is_stock'] = $is_stock?1:0;
            $v['stock'] = $is_stock;
            $v['shop_info'] = $shop_info;

        }
        return $list;
    }


    //获取订单基本信息（成本）
    public function getOrderCostPrice($order_id)
    {
        $data = $this
            ->where(['id'=>$order_id])
            ->field('id,amount')
            ->find();
        $db = Db::connect(config('ddxx'));
        $price = $db->name('finance')->where('order_id','=',$order_id)->value('price');
        $order_goods = (new OrderGoodsModel())
            ->where('order_id','=',$order_id)
            ->field('oprice,num')
            ->select();
        $num = 0;
        $prices = 0;
        foreach ($order_goods as &$v) {
            $num += $v['num'];
            $prices += round($v['oprice'] * $v['num'],2);
        }
        $price = round($price * $num,2);
        if (empty($price)) {
            $price = $prices;
        }
        $data['oprice'] = $price;
        return $data;
    }

    //删除订单
    public function deleteOrder($id)
    {
        $orderInfo = self::get($id);
        $msg = '将订单删除,订单号: '.$orderInfo->sn;
        $result = $this->allowField(true)->save(['isDel' => 1,'msg' => $msg],['id' => $id]);

        if($result){
            return true;
        }
        return false;
    }

    //财务对账
    public function checkOrder($name,$ids)
    {
        $fail = 0;
        $succ = 0;
        $db = Db::connect(config('ddxx'));
        foreach ($ids as $k=>$v){
            $status = $db->name('order')->where(['id'=>$v])->value('check_status');
            $status = (int) $status;
            if($status == 1){
                $new_status = 0;
                $handle = '已对账';
            }else{
                $new_status = 1;
                $handle = '未对账';
            }
            $res = $db->name('order')->where(['id'=>$v])->update(['check_status'=>$new_status]);
            if($res){
                $succ+=1;
            }else{
                $fail+=1;
            }
        }
        if($fail>0){
            return 0;
        }
        return 1;
    }

    //关闭订单(取消)
    public function closeOrder($id,$admin_name)
    {
        $db = Db::connect(config('ddxx'));
        $db->startTrans();
        $this->startTrans();
        $orderInfo = $db->name('order')->where(['id'=>$id])->find();
        if ($orderInfo['order_status']['status'] == -1) {
            return false;
        }
        $msg = '将订单关闭,订单号: '.$orderInfo['sn'];
        $refund_no = '42000000'.date('W').date('Ymd').rands(10);//支付流水号
        //订单日志数据
        $datas = [];
        $datas['order_id'] = $id;
        $datas['send_status'] = 0;
        $datas['name'] = $admin_name;
        $datas['logs'] = '订单处理:取消订单';
        $datas['addtime'] = time();
        try {
            //生成一条退货单数据
            $new_data = $orderInfo;
            unset($new_data['id']);
            $new_data['pay_sn'] = $refund_no;
            $new_data['sn'] = time().rands(2);
            $new_data['amount'] = ($orderInfo['amount']<=0)?0:- $orderInfo['amount'];
            $new_data['addtime'] = time();
            $new_data['order_status'] = -1;
            $new_data['overtime'] = time();
            $new_data['paytime'] = time();
            $this->allowField(true)->save($new_data);
            $refund_order_id = $this->id;
            // $refund_order_id = $this->id;
            $goods_list = $db->name('order_goods')->where(['order_id'=>$id])->field('id,order_id',true)->select();
            foreach($goods_list as $k=> $v){
                $goods_list[$k]['order_id'] = $refund_order_id;
                $goods_list[$k]['price']    = - $v['price'];
                $goods_list[$k]['ticket_deduction']  = -$v['ticket_deduction'];
                $goods_list[$k]['status']        = -1;
            }
            $db->name('order_goods')->insertAll($goods_list);
            if ($orderInfo['pay_status'] == 0) {
                $datas['order_status'] = -7;
                $datas['pay_status'] = 0;
                $datas['content'] = '长时间未支付,管理员取消当前订单';
            }elseif ($orderInfo['pay_status'] == 1 && $orderInfo['order_status'] == 0) {
                $datas['order_status'] = -1;
                $datas['pay_status'] = 1;
                $datas['content'] = '管理员取消订单';
                //退款
                if ($orderInfo['pay_way'] == 1) {
                    $refund_data['order_sn'] = $orderInfo['sn'];
                    $refund_data['transaction_id'] = $orderInfo['pay_sn'];
                    $refund_data['refund_no'] = $refund_no;
                    $refund_data['total_price'] = $orderInfo['amount'] * 100;
                    $refund_res = (new PayModel())->wxRefund($refund_data);
                }elseif($orderInfo['pay_way'] == 3){
                    $refund_res = (new PayModel())->balanceRefund($id);
                }
                if ($refund_res == false) {
                    $db->rollback();
                    $this->rollback();
                    return $refund_res;
                }
            }
            //退券
            (new UserTicketModel())->updateOrderTicketStatus($id,1);
            $res = $db->name('order')->where(['id'=>$id])->update(['order_status' => -1,'canceltime'=>time()]);
            // $res = $this->allowField(true)->save(['order_status' => -1,'canceltime'=>time(),'returntime'=>time(),'msg' => $msg],['id' => $id]);
            if (!$res) {
                $db->rollback();
                $this->rollback();
                // var_dump($e);die;
                return false;
            }
            //取消成功需要释放库存
            //此时删除临时数据
            $list = model('order.OrderTempData')->where(['order_id'=>$id])->field('shop_id,item_id,num')->select();
            if ($list) {
                foreach ($list as $v) {
                    $db->name('shop_item')->where(['shop_id'=>$v['shop_id'],'item_id'=>$v['item_id']])->setDec('stock_ice', $v['num']);
                }
                model('order.OrderTempData')->where(['order_id'=>$id])->delete();
            }
            //存储操作日志
            (new OrderLogsModel())->allowField(true)->save($datas);
            $db->commit();
            $this->commit();
            return true;
        } catch (Exception $e) {
            $db->rollback();
            $this->rollback();
            // var_dump($e);die;
            return false;
        }
    }

    // 获取订单基本信息
    public function getEditPriceInfo($order_id)
    {
        $data = $this->where('id','=',$order_id)->field('amount,postage,id')->find();
        $goodslist = (new OrderGoodsModel())
            ->alias('a')
            ->field('id,item_id,subtitle,price,num')
            ->where(['order_id'=>$order_id])
            ->select();
        foreach ($goodslist as &$v) {
            $v['content'] = Db::connect(config('ddxx'))->name('discount')->where(['order_id'=>$order_id,'goods_id'=>$v['id']])->value('content');
        }
        $data['goods_list'] = $goodslist;
        return $data;

    }

    //查看改价信息
    public function showEditPriceInfo($order_id)
    {
        $info = $this
            ->alias('a')
            ->join('tf_discount b','a.id=b.order_id')
            ->field('a.id,a.postage,a.amount,b.price,b.disprice,b.num,b.content')
            ->where(['a.id'=>$order_id])
            ->select();
        return $info;
    }
    //获取服务卡订单列表
    public function ticket_list($where,$res=null){
        $data = Db::name("ticket_user_pay")
            ->alias("a")
            ->where($where)
            ->field("o.id,o.sn,m.nickname,o.member_id,a.mobile,a.ticket_id,a.real_price,o.overtime,o.pay_way,o.waiter,o.waiter_id,a.status,a.shop_id,o.order_status,a.refund,a.id as p_id,o.is_examine,o.refund_num")
            ->withAttr("overtime",function($value,$data){
                return date("Y-m-d H:i:s",$data['overtime']);
            })
            ->withAttr("pay_way",function($value,$data){
                return $this->getPayWayAttr($data['pay_way']);
            })
            ->withAttr("status",function($value,$data){
                if($data['status']==0){
                    return "未激活";
                }else if($data['status']==1){
                    return "使用中";
                }else if($data['status']==2){
                    return "使用完";
                }else if($data['status']==3){
                    return "已过期";
                }else if($data['status']==4){
                    return "已退卡";
                }
            })
            ->withAttr("order_status",function($value,$data){
                return $this->getOrderListStatusAttr($data['order_status']);
            })
            ->join("ddxm_order o","a.order_id = o.id")
            ->join('ddxm_member m','a.member_id = m.id')
            ->order("o.add_time","desc")
            ->page($res['page'],$res['limit'])
            ->select();
        foreach($data as $k=>$v){
            $data[$k]['shop_name']=$this->getOrderName($v['shop_id']);
        }
        return $data;
    }
    //服务卡订单详情
    public function ticket_details($value){
        $data = db::name("order")
        ->alias("a")
        ->where("a.id",intval($value['id']))
        ->field("a.sn,a.shop_id,a.is_online,a.waiter,a.old_amount,t.price,t.real_price,t.member_id as member,a.amount,a.pay_way,a.overtime,t.id,t.create_time,t.start_time,t.end_time,t.over_time")
        ->withAttr("member",function($value,$data){
            return  $this->getUserName("member",$data['member']);
        })
        ->withAttr("shop",function($value,$data){
            $shop = db::name("shop")->where("id",$data['shop_id'])->find();
            $code = !empty($shop['shop_code'])?$shop['code']."-":"";
            $code .=$shop['name'];
            return $code;
        })
        ->withAttr("is_online",function($value,$data){
            return $data['is_online'] ==0?"收银台":"线上商城";
        })
        ->withAttr("overtime",function($value,$data){
            return date("Y-m-d H:i:s",$data['overtime']);
        })
        ->withAttr("start_time",function($value,$data){
             if($data['start_time']==0){
                return "未激活";
            }
            return date("Y-m-d H:i:s",$data['start_time']);
        })
        ->withAttr("create_time",function($value,$data){
            return date("Y-m-d H:i:s",$data['create_time']);
        })
        ->withAttr("end_time",function($vlaue,$data){
            if($data['end_time']==0){
                return "未激活";
            }
            return date("Y-m-d H:i:s",$data['end_time']);
        })
        ->withAttr("over_time",function($vlaue,$data){
            if($data['over_time']==0){
                if($data['end_time'] ==0){
                    return "未激活";
                }else{
                    return "无限制";
                }
            }
            return date("Y-m-d H:i:s",$data['over_time']);
        })
        ->withAttr("pay_way",function($value,$data){
            return $this->getPayWayAttr($data['pay_way']);
        })
        ->join("ddxm_ticket_user_pay t",'a.id = t.order_id')
        ->find();
        /*echo db::name("order")->getLastSql();*/
        return $data;
    }
    // 服务卡内服务列表
    public function ticketgoodslist($value){
        $data = Db::name("ticket_use")
        ->alias("a")
        ->where("a.ticket_id",intval($value['id']))
        ->field("a.id,a.service_id,a.num,a.s_num,s.sname,a.r_year,a.r_month,a.r_day")
        ->join("ddxm_service s","a.service_id = s.id")
        ->withAttr("num",function($value,$data){
            if($data['num'] ==0){
                return "无限制";
            }
            return $data['num'];
        })
        ->withAttr("s_num",function($value,$data){
            if($data['num'] ==0){
                return "无限制";
            }
            return $data['s_num'];
        })
        ->withAttr("r_year",function($value,$data){
            if($data['r_year']==0){
                return "无限制";
            }
            return $data['r_year'];
        })
        ->withAttr("r_month",function($value,$data){
            if($data['r_month']==0){
                return "无限制";
            }
            return $data['r_month'];
        })
        ->withAttr("r_day",function($value,$data){
            if($data['r_day']==0){
                return "无限制";
            }
            return $data['r_day'];
        })
        ->select();
        return $data;
    }
    // 获取服务器中门店名称
    public function getOrderName($id){
        $data = Db::name("shop")->where("id",$id)->value("name");
        return $data;
    }
    //完成订单(管理员收货)
    public function completeOrder($id,$admin_name)
    {
        $orderInfo = self::get($id);
        $msg = '管理员手动收货，订单编号: '.$orderInfo->sn;
        $db = Db::connect(config('ddxx'));
        self::startTrans();
        $db->startTrans();
        try {
             $this->allowField(true)->save(['order_status' => 2,'overtime'=>time(),'msg' => $msg],['id' => $id]);
            $finance_info = $db->name('finance')->where(['order_id'=>$id])->field('status,id')->find();
            if ($finance_info && $finance_info['status'] == 0){
                $db->name('finance')->where(['id'=>$finance_info['id']])->update(['status'=>1]);
            }
            //存储操作日志
            //此时删除临时数据
            $list = model('order.OrderTempData')->where(['order_id'=>$id])->field('shop_id,item_id,num')->select();
            if ($list) {
                foreach ($list as $v) {
                    if ($v['shop_id']) {
                        model('shop.Stock')->updateItemStock($v['item_id'],$v['shop_id'],$v['num'],$id,6);
                        $db->name('shop_item')->where(['shop_id'=>$v['shop_id'],'item_id'=>$v['item_id']])->setDec('stock_ice', $v['num']);
                    }
                }
                model('order.OrderTempData')->where(['order_id'=>$id])->delete();
            }
            (new IntegralModel())->addIntegral($id);
            $datas['order_id'] = $id;
            $datas['order_status'] = 2;
            $datas['pay_status'] = 1;
            $datas['send_status'] = 0;
            $datas['name'] = $admin_name;
            $datas['logs'] = '订单处理:完成订单';
            $datas['content'] = '管理员手动收货';
            $datas['addtime'] = time();
            (new OrderLogsModel())->allowField(true)->save($datas);
            self::commit();
            $db->commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            $db->rollback();
            return false;
        }

    }

    //修改价格
    public function modifyPrice($params)
    {
        $params = $params['data'];
        // var_dump($params);die;
        $orderInfo = self::get($params['order_id']);
        $all_price = $params['postage'];
        foreach ($params['goods_list'] as $v) {
            $all_price += round($v['price'],2);
        }

        $msg = $params['name'].'将id为：'.$orderInfo->id .' 的订单价格修改为'.$all_price . '元，其中包含更改后的邮费'.$params['postage'].'元';
        try {
            Db::startTrans();
            self::startTrans();
            (new OrderGoodsModel())->startTrans();
            (new OrderLogsModel())->startTrans();
            $result = $this->allowField(true)->save(['amount'=>$all_price,'msg'=>$msg,'postage'=>$params['postage']],['id' => $orderInfo->id]);
            if ($result || $result === 0) {
                $datas['order_id'] = $orderInfo->id;
                $datas['order_status'] = 1;
                $datas['pay_status'] = 1;
                $datas['send_status'] = 0;
                $datas['name'] = $params['name'];
                $datas['logs'] = '修改订单价格【'.$orderInfo->amount.'-->'.$all_price.',含邮费】';
                $datas['addtime'] = time();
                (new OrderLogsModel())->allowField(true)->save($datas);//储存操作日志
            }
            foreach ($params['goods_list'] as $v) {
                $goods_data['price'] = round($v['price'] / $v['num'],2);
                $goods_data['msg'] = '修改了价格';
                //优惠数据
                $noFreeprice = (new OrderGoodsModel())
                    ->where(['id'=>$v['goods_id']])
                    ->value('price');
                $discount_data = [];
                $discount_data['price'] = $noFreeprice;
                $sumPrice = round($noFreeprice*$v['num'],2);//商品的原来的zong价格
                $discount_data['disprice'] = round($sumPrice - $v['price'] - $params['postage'],2)/$v['num'];
                $res = (new OrderGoodsModel())->allowField(true)->save($goods_data,['id'=>$v['goods_id']]);
                if ($res) {
                    $exsit_dis = Db::connect(config('ddxx'))->name('discount')->where(['order_id'=>$orderInfo->id,'goods_id'=>$v['goods_id']])->value('id');
                    if ($exsit_dis){
                        Db::connect(config('ddxx'))->name('discount')->where(['id'=>$exsit_dis])->update($discount_data);
                    }else{
                        //记录改价信息
                        $discount_data['order_id'] = $params['order_id'];
                        $discount_data['goods_id'] = $v['goods_id'];
                        $discount_data['content'] = isset($v['eprice_remark'])?$v['eprice_remark']:$params['name'].'进行商品改价';
                        $discount_data['type'] = 2;
                        $discount_data['num'] = $v['num'];
                        $discount_data['addtime'] = time();
                        Db::connect(config('ddxx'))->name('discount')->insert($discount_data);
                    }
                }
            }
            self::commit();
            Db::commit();
            (new OrderGoodsModel())->commit();
            (new OrderLogsModel())->commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            Db::rollback();
            (new OrderGoodsModel())->rollback();
            (new OrderLogsModel())->rollback();
            // var_dump($e);die;
            return false;
        }
    }

    //添加成本
    public function addCostPrice(array $price_list = [],$order_id = 0)
    {
        $price = 0;
        $faker_price = 0;
        $postage = 0;
        $db = Db::connect(config('ddxx'));
        $db->startTrans();
        try {
            foreach ($price_list as $v) {
                //这里面来修改订单商品里面的成本价
                if ($v['id']) {
                    $num = (new OrderGoodsModel())->allowField(true)->where(['id'=>$v['id']])->value('num');
                    $goods_data['oprice'] = round($v['cost_price']/$num,2);
                    $goods_data['faker_price'] = round($v['faker_price']/$num,2);
                    if (isset($v['send_by_purchase'])){
                        $goods_data['send_by_purchase'] = $v['send_by_purchase'];
                    }
                    (new OrderGoodsModel())->allowField(true)->save($goods_data,['id'=>$v['id']]);
                }
                if ($v['id'] == 0) {
                   $postage =  $v['cost_price'];
                }
                $price += round($v['cost_price'],2);
                $faker_price += round($v['faker_price'],2);
            }
            $exist_id = $db->name('finance')->where(['order_id'=>$order_id])->value('id');
            if (config('finance.show_cost_finance')) {
                $faker_price = $price;
            }
            if ($exist_id) {
                $res = $db->name('finance')->where(['id'=>$exist_id])->update(['price'=>$faker_price]);
            }else{
                $info=  $db->name('order')->where(['id'=>$order_id])->field('shop_id,sn')->find();
                $finance['shop_id'] = $info['shop_id'];
                $finance['content'] = '线上商城手动添加订单成本,其中邮费【'.$postage.'】元';
                $finance['price'] = $faker_price;
                $finance['cost_price'] = $price;
                $finance['status']  = 1;
                $finance['order_id'] = $order_id;
                $finance['type'] = 1;
                $finance['sn'] = $info['sn'];
                $finance['addtime'] = time();
                $res = $db->name('finance')->insert($finance);
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
             // var_dump($e->getMessage());die;
            return false;
        }
    }


    //添加成本
    public function saveOrderTempData(array $item_list = [],$order_id = 0)
    {
        $db = Db::connect(config('ddxx'));
        $db->startTrans();
        try {
            $item_data = [];
            foreach ($item_list as $v) {
                $data = [];
                $data['order_id'] = $order_id;
                $data['shop_id'] = $v['shop_id'];
                $data['order_goods_id'] = $v['order_goods_id'];
                $data['item_id'] = $v['item_id'];
                //这里面来修改订单商品里面的成本价
                $id = $db->name('order_temp_data')->where(['order_id'=>$order_id,'item_id'=>$v['item_id']])->value('id');
                $data['cost_price'] = round($v['cost_price']/$v['num'],2);
                $data['faker_price'] = round($v['faker_price']/$v['num'],2);
                if ($id && $v['shop_id'] == 0){
                    $data['num'] = 0;
                }else{
                    $data['num'] = $v['num'];
                }
                //如果存在冻结库存 那不选库存的时候就要释放
                $shop_id = $db->name('order_temp_data')->where(['order_id'=>$order_id,'item_id'=>$v['item_id']])->value('shop_id');
                if ($v['shop_id'] == 0 && $shop_id) {
                    $db->name('shop_item')->where(['shop_id'=>$shop_id,'item_id'=>$v['item_id']])->setDec('stock_ice',$v['num']);
                }elseif($v['shop_id'] > 0 && $shop_id == 0){
                    $db->name('shop_item')->where(['shop_id'=>$v['shop_id'],'item_id'=>$v['item_id']])->setInc('stock_ice',$v['num']);
                }
                if ($id) {
                    $db->name('order_temp_data')->where(['id'=>$id])->update($data);
                }else{
                    $data['addtime'] = time();
                    $db->name('order_temp_data')->insert($data);
                }
            }
        $db->commit();
        return true;
        } catch (\Exception $e) {
            $db->rollback();
            // var_dump($e->getMessage());die;
            return false;
        }
    }

    //修改收货人信息
    public function modifyReceiptInfo($params)
    {
        $orderInfo = self::get($params['order_id']);
        $db = Db::connect(config('ddxx'));
        $db->startTrans();
        try {
            $params['msg'] = '修改订单收货人信息,订单id: '.$params['order_id'];
            $result = $this->allowField(true)->save($params,['id' => $params['order_id']]);
            $declare = new \app\admin\model\user\UserDeclareModel();
            (new \app\admin\model\user\UserModel())->allowField(true)->save(array('realname'=>$params['name'],'msg'=>'修改用户真实姓名'),['id'=>$params['member_id']]);
            if ($declare->where(['member_id'=>$params['member_id']])->find()) {
                $res = $declare->allowField(true)->save($params,['member_id'=>$params['member_id']]);
            }else{
                $res = $declare->allowField(true)->save($params);
            }

            if ($result && $res || $result === 0 && $res === 0) {
                $datas['order_id'] = $params['order_id'];
                $datas['order_status'] = $orderInfo['order_status']['status'];
                $datas['pay_status'] = $orderInfo['pay_status'];
                if ($orderInfo->send_way == '快递') {
                    $order_sttaus = 2;
                }elseif($orderInfo->send_way == '自提'){
                    $order_sttaus = 1;
                }elseif($orderInfo->send_way == '配送'){
                    $order_sttaus = 3;
                }
                $datas['send_status'] = $order_sttaus;
                $datas['name'] = $params['admin_name'];
                $datas['logs'] = '修改订单收货人信息,订单id:'.$params['order_id'];
                $datas['addtime'] = time();
                (new OrderLogsModel())->allowField(true)->save($datas);//
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }
    //发货或修改物流
    public function modifyOrSend($params)
    {

        $data = [];
        if (empty($params['express_id'])){
            $data['order_id'] = $params['order_id'];
            $data['express_sn'] = '自提';
            $data['express_code'] = 0;
            $data['express_name'] = '自提';
            $data['tihuo_time'] = 0;
            $data['type'] = 0;
        }else{
            //获取该订单物流信息
            $expressInfo = self::getExpressInfo($params['express_id']);
            //组装数组
            $data['order_id'] = $params['order_id'];
            $data['express_sn'] = $params['express_sn'];
            $data['express_code'] = $expressInfo['express_code'];
            $data['express_name'] = $expressInfo['express_name'];
            $data['type'] = 1;
        }
        //订单操作日志内容
        $datas = [];
        $datas['order_id'] = $params['order_id'];
        $datas['order_status'] = 1;
        $datas['pay_status'] = 1;
        $datas['send_status'] = 1;
        $datas['name'] = $params['name'];
        $datas['addtime'] = time();
        if($params['orderExpress_id']){
            //修改物流
            $data['id'] = $params['orderExpress_id'];
            $result = (new OrderExpressModel())->allowField(true)->save($data,['id' => $params['orderExpress_id']]);
            if ($result || $result === 0) {
                $datas['logs'] = '修改物流信息';
//                $datas['content'] = '因...原因修改物流信息';
                (new OrderLogsModel())->allowField(true)->save($datas);//储存操作日志
                return true;
            }
            return false;
        }else{
            //添加物流数据  改变订单状态
            $result = (new OrderExpressModel())->allowField(true)->save($data);
            if ($result || $result === 0) {
                //添加日志
                $msg = '将订单状态由 待发货 修改为 待收货';
                $this->allowField(true)->save(array('order_status'=>1,'sendtime'=>time(),'msg'=>$msg),['id' => $params['order_id']]);
                $datas['logs'] = '平台发货';
//                $datas['content'] = '';
                (new OrderLogsModel())->allowField(true)->save($datas);//储存操作日志
                //记录销量数据
                //组装销量表数组
                $db = Db::connect(config('ddxx'));
                $list = $db->name('order')->alias('a')
                    ->join('tf_order_goods b','a.id=b.order_id')
                    ->where(['a.id'=>$params['order_id']])
                    ->field('a.shop_id,b.item_id,b.num,b.send_by_purchase')
                    ->select();
                foreach ($list as $k=>$v){
                    if($v['send_by_purchase']==1){
                        (new CollectModel())->countSale($v['shop_id'],$v['item_id'],$v['num'],1,1);
                    }else{
                        (new CollectModel())->countSale($v['shop_id'],$v['item_id'],$v['num'],1,2);
                    }
                }
                return true;
            }
            return false;
        }
    }
    //获取门店数据
    public static function getShopList($where = '',$isObj = 0)
    {
        if ($where != ''){
            $listObj= (new ShopModel())->field('id,code,name')->where($where)->order('addtime', 'desc')->select();
        }else{
            $listObj = (new ShopModel())->field('id,code,name')->order('addtime', 'desc')->select();
        }
        if ($isObj){
            return $listObj;
        }
        return collection($listObj)->toArray();
    }
    //获取对应的订单
    public function getBasicOrderList($where = '',$field='')
    {
        if ($where != ''){
            $listObj= $this->field('id,addtime,pay_status,order_status')->where($where)->order('addtime', 'desc')->select();
        }else{
            $listObj = $this->field('id,addtime,pay_status,order_status')->order('addtime', 'desc')->select();
        }
        return collection($listObj)->toArray();
    }
    //获取快递公司列表
    public function getExpressInfo($id = '')
    {
        if (!empty($id)) {
            $data = $this->expressInfo()->find($id);
        }else{
            $data = $this->expressInfo()->select();
        }
        return $data;
    }
    //获取订单物流信息
    public function getOrderExpress($id)
    {
        $orderExpress = (new OrderExpressModel())->where(['order_id'=>$id])->find();
        if ($orderExpress) {
            return $orderExpress;die;
        }
        return false;
    }
    //处理订单
    public function modifyOrder($data,$userinfo)
    {
        switch ($data['send_way']) {
            case 0:
                //门店自提
                //修改发货方式
                $msg = '门店订单处理,订单id:'.$data['order_id'].'【用户自提】';
                $tihuo = empty($data['date'])?time():strtotime($data['date']);
                $order_data['send_way'] = 1;
                $order_data['order_status'] = 1;
                $order_data['msg'] = $msg;
                $order_data['dealwithtime'] = time();
                $order_data['sendtime'] = $tihuo;
                $result = $this->allowField(true)
                    ->save($order_data,['id'=>$data['order_id']]);
                if ($result){
                    $datas['order_id'] = $data['order_id'];
                    $datas['order_status'] = 1;
                    $datas['pay_status'] = 1;
                    $datas['send_status'] = 1;
                    $datas['name'] = $userinfo['name'];
                    $datas['logs'] = $msg;
                    $riqi = empty($data['date'])?date('Y-m-d',time()):$data['date'];//日期
                    $datas['content'] = '约定提货日期:'.$riqi;
                    $datas['addtime'] = time();
                    (new OrderLogsModel())->allowField(true)->save($datas);//储存操作日志
                    (new OrderExpressModel())
                        ->allowField(true)
                        ->save(array('order_id'=>$data['order_id'],'express_sn'=>'自提','type'=>0,'tihuo_time'=>$tihuo));
                    return true;
                }
                return false;
                break;
            case 1://门店快递
                $msg = '门店订单处理,订单id:'.$data['order_id'].'【门店发货】';
                //快递信息
                $expressinfo = $this->getExpressInfo($data['express_id']);
                $result = $this->allowField(true)
                    ->save(array('send_way' => 0,'order_status'=>1,'msg'=>$msg,'dealwithtime'=>time(),'sendtime'=>time()),['id'=>$data['order_id']]);
                if ($result){
                    $datas['order_id'] = $data['order_id'];
                    $datas['order_status'] = 1;
                    $datas['pay_status'] = 1;
                    $datas['send_status'] = 1;
                    $datas['name'] = $userinfo['name'];
                    $datas['logs'] = $msg;
                    $datas['addtime'] = time();
                    (new OrderLogsModel())->allowField(true)->save($datas);

                    (new OrderExpressModel())
                        ->allowField(true)
                        ->save(array('order_id'=>$data['order_id'],'express_sn'=>$data['express_sn'],'express_name'=>$expressinfo->express_name,'express_code'=>$expressinfo->express_code));
                    return true;
                }
                return false;
                break;
            case 2:
                //平台代发货
                //修改发货方式
                $msg = '门店订单处理,订单id:'.$data['order_id'].'【平台代发货】';
                $result = $this->allowField(true)
                    ->save(array('send_way' => 2,'order_status'=>0,'msg'=>$msg,'dealwithtime'=>time()),['id'=>$data['order_id']]);
                if ($result){
                    $datas['order_id'] = $data['order_id'];
                    $datas['order_status'] = 0;
                    $datas['pay_status'] = 1;
                    $datas['send_status'] = 0;
                    $datas['name'] = $userinfo['name'];
                    $datas['logs'] = $msg;
                    $datas['addtime'] = time();
                    (new OrderLogsModel())->allowField(true)->save($datas);
                    return true;
                }
                return false;
                break;
        }
    }
    //单独修改订单状态function
    public function updateOrderStatus($order_id = 0)
    {
        $msg = '系统自动处理订单状态:关闭订单';
        $db = Db::connect(config('ddxx'));
        $db->startTrans();
        try {
            $res = $this->allowField(true)->save(['order_status'=>-7,'msg'=>$msg,'canceltime'=>time()],['id'=>$order_id]);
            $list = model('order.OrderTempData')->where(['order_id'=>$order_id])->field('shop_id,item_id,num')->select();
            if ($list) {
                foreach ($list as $v) {
                    $db->name('shop_item')->where(['shop_id'=>$v['shop_id'],'item_id'=>$v['item_id']])->setDec('stock_ice', $v['num']);
                }
                model('order.OrderTempData')->where(['order_id'=>$order_id])->delete();
            }
            //应该添加一条退款信息或者自动退款
            if ($res) {
                $datas['order_id'] = $order_id;
                $datas['order_status'] = -7;
                $datas['pay_status'] = 1;
                $datas['send_status'] = 0;
                $datas['name'] = '系统';
                $datas['logs'] = '订单处理【平台代发货】';
                $datas['content'] = '订单超时未处理,订单取消,等待退款';
                $datas['addtime'] = time();
                (new OrderLogsModel())->allowField(true)->save($datas);
                $db->commit();
                return true;
            }
            $db->rollback();
            return false;
        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }

    //计算商品税费
    public function lvnums($item_id)
    {
        $lv = Db::connect(config('ddxx'))
            ->name('item')
            ->join('tf_lv','tf_item.lvid=tf_lv.id')
            ->field('tf_lv.lv,tf_item.price')
            ->where("tf_item.id=".$item_id)
            ->where("tf_item.type_id=3")
            ->find();
        if ($lv) {
            return round($lv['lv'] * $lv['price'] * 0.01,2);
        }
        return 0;
    }
    //删除超时未支付订单
    public function delTimeOutOrder($order_id = 0)
    {
        // $order = OrderModel::get($order_id);
        $datas['isDel'] = 1;
        $datas['msg'] = '删除超时未支付订单(将isdel改为1)';
        // $order->isDel = 1;
        // $order->msg = '删除超时未支付订单(将isdel改为1)';
        $res = $this->allowField(true)->save($datas,['id'=>$order_id]);
        if ($res) {
            return true;
        }
        return false;
    }
    /*
    统计折线图数据
    $pid:加盟商的id
     */
    public function orderwan($pid)
    {
        if($pid==0){
            $where='1=1';
        }else{
            $where['shop_id']=$pid;
        }

        $indexinfo = $this->table($this->table)
            ->field("count(id),FROM_UNIXTIME(addtime,'%Y年%m月')")
            ->where('order_status=2')
            ->where($where)
            ->group("FROM_UNIXTIME(addtime,'%Y年%m月')")
            ->select();
        $indexinfo = json_decode(json_encode($indexinfo,true));
        //print_r($indexinfo);
        return $indexinfo;
    }

    public function getRechargeHistroy($where){
        $data = Db::connect(config('ddxx'))->name('order')->alias('o')
            ->join('tf_member m','o.member_id=m.id')
            ->where($where)
            ->field('o.addtime,o.sn,o.is_online,o.pay_way,o.amount,o.waiter,m.mobile,m.nickname');
        return $data;
    }

    public function getCount($where){
        $num = Db::connect(config('ddxx'))->name('order')->alias('o')
                ->join('tf_member m','o.member_id=m.id')
                ->where($where)
                ->field('o.addtime,o.sn,o.is_online,o.pay_way,o.amount,o.waiter,m.mobile,m.nickname')
                ->count();
        return $num;
    }
}
