<?php
// +----------------------------------------------------------------------
// | 商城订单模型
// +----------------------------------------------------------------------
namespace app\mall\model\order;
use app\admin\model\Adminlog;
use app\wxshop\model\coupon\CouponModel;
use think\Exception;
use think\Model;
use think\Db;
class OrderModel extends Model {
    protected $table = 'ddxm_order';
    protected $table_goods = "ddxm_order_goods";
    public function index(){
        return $this->table($this->table)->alias("a")->field("a.id,a.member_id,a.sn,a.type,a.pay_sn,a.pay_status,a.send_way,a.pay_way,a.paytime,a.sendtime,a.fixtime,a.order_status,a.is_online,m.nickname,m.mobile,a.deliver_status")->join("member m","a.member_id = m.id");
    }
    public function order_goods(){
        return $this->table($this->table_goods);
    }

    //拼接订单信息
    public function getMessageAttr($val,$data){
        $pay_way = [
            ''=>'未付款',
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
            12 => '超级汇买',
            13 => '限时余额',
            99 => '异常充值'
        ];
        $pay  = [
            0=>"待付款",
            1=>"已付款",
            -1=>"取消订单",
        ];
        if( $data['shop_id'] == 0 ){
            $shopName = '公司总部';
        }else{
            $shopName = Db::name('shop')->where('id',$data['shop_id'])->value('name');
        }
        $postage = $data['postage']==0?'免邮':$data['postage'].'元';
        $message = "<p>订单号：".$data['sn']."</p>
				<p>所属门店：".$shopName."</p>
				<p>付款金额(包含运费)：".$data['amount']." 元</p>
				<p>订单运费：$postage</p>
				<p>付款方式：".$pay_way[$data['pay_way']]."</p>";
        return $message;
    }

    //拼接订单信息
    public function getMessage2Attr($val,$data){
        $pay_way = [
            ''=>'未付款',
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
            12 => '超级汇买',
            13 => '限时余额',
            99 => '异常充值'
        ];
        $pay  = [
            0=>"待付款",
            1=>"已付款",
            -1=>"取消订单",
        ];
        if( $data['shop_id'] == 0 ){
            $shopName = '公司总部';
        }else{
            $shopName = Db::name('shop')->where('id',$data['shop_id'])->value('name');
        }
        $postage = $data['postage']==0?'免邮':$data['postage'].'元';
        $message = "<p>订单号：".$data['sn']."</p>
				<p>所属门店：".$shopName."</p>
				<p>退款金额：".$data['money']." 元</p>
				<p>付款方式：".$pay_way[$data['pay_way']]."</p>";
        return $message;
    }

    /***
     * 会员信息
     */
    public function getMemberInfoAttr( $val,$data ){
        $paytime = $data['paytime'];
        if( empty($paytime) ){
            $pay = '未支付';
        }else if( strlen($paytime)==14 ){
            $pay = date('Y-m-d H:i:s',strtotime($paytime));
        }else{
            $pay = date('Y-m-d H:i:s',$paytime);
        }
        $member = "<p>会员号：".$data['mobile']."</p>
				<p>会员名：".$data['wechat_nickname']."</p>
				<p>下单时间：".date('Y-m-d H:i:s',$data['add_time'])."</p>
				<p>支付时间：".$pay."</p>";
        return $member;
    }

    /***
     * 拼接订单信息
     * @param $val
     * @param $data
     * @return \think\db\Query
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getItemListAttr($val,$data){
        $item = Db::name('order_goods')->where('order_id',$data['id'])->order('id asc')->field('subtitle,attr_name')->select();
        $tt = '';
        foreach ($item as $key => $value) {
            $spe = empty($value['attr_name'])?'无':$value['attr_name'];
            $tt .= "<p>".$value['subtitle']."&nbsp;&nbsp;(".$spe.")</p>";
        }
        return $tt;
    }

    /***
     * 拼接订单信息
     * @param $val
     * @param $data
     * @return \think\db\Query
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getItemList2Attr($val,$data){
        $where = [];
//        $where[] = ['order_id','eq',$data['id']];
        $where[] = ['id','eq',$data['goods_id']];
        $where[] = ['refund_status','neq',0];
        $item = Db::name('order_goods')
            ->where($where)
            ->field('subtitle,attr_name')->order('id asc')->select();
        $tt = '';
        foreach ($item as $key => $value) {
            $spe = empty($value['attr_name'])?'无':$value['attr_name'];

            if($data['order_distinguish'] == 5)
            {
                //打包活动拼接
                $tt .= "<p><font color='red'>[打包活动]</font>".$value['subtitle']."&nbsp;&nbsp;(".$spe.")</p>";
            }else{
                $tt .= "<p>".$value['subtitle']."&nbsp;&nbsp;(".$spe.")</p>";
            }

        }
        return $tt;
    }

    /***
     * 拼接单价信息
     * @param $val
     * @param $data
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPriceListAttr($val,$data){
        $item = Db::name('order_goods')->where('order_id',$data['id'])->field('real_price,refund')->order('id asc')->select();
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p> ￥".$value['real_price']." 元 <span> &nbsp;&nbsp;X".$value['refund']."</span></p>";
        }
        return $tt;
    }

    /***
     * 拼接单价信息--- 只查看退单
     * @param $val
     * @param $data
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPriceList2Attr($val,$data){

        $where = [];
//        $where[] = ['order_id','eq',$data['id']];
        $where[] = ['refund_status','neq',0];
        $where[] = ['id','eq',$data['goods_id']];
        $item = Db::name('order_goods')->where($where)->field('real_price,num')->order('id asc')->select();
        //获取打包活动详细
        $pack = db('st_pack')->where(['id'=>$data['event_id'],'is_delete'=>0])->find();

        $tt = '';
        foreach ($item as $key => $value) {

            if($data['order_distinguish'] == 5)
            {
                //打包活动
                $tt .= "<p> ￥".round(($pack['p_condition1']/$pack['p_condition2'])*$value['num'])." 元 <span> &nbsp;&nbsp;X".$value['num']."</span></p>";
            }else{
                $tt .= "<p> ￥".$value['real_price']." 元 <span> &nbsp;&nbsp;X".$value['num']."</span></p>";
            }

        }
        return $tt;
    }

    /***
     * 拼接单成本成本信息
     * @param $val
     * @param $data
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCostList2Attr($val,$data){

        $where = [];
//        $where[] = ['order_id','eq',$data['id']];
        $where[] = ['refund_status','neq',0];
        $where[] = ['id','eq',$data['goods_id']];

        $item = Db::name('order_goods')->where($where)->field('id,oprice,num')->order('id asc')->select();
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p> ￥".$value['oprice']." 元 <span> &nbsp;&nbsp;X".$value['num']."</span></p>";
        }
        return $tt;
    }

    /***
     * 拼接单成本成本信息---退单
     * @param $val
     * @param $data
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCostListAttr($val,$data){
        $item = Db::name('order_goods')->where('order_id',$data['id'])->field('id,oprice,num')->order('id asc')->select();
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p> ￥".$value['oprice']." 元 <span> &nbsp;&nbsp;X".$value['num']."</span></p>";
        }
        return $tt;
    }

    /***
     * 拼装条形码
     * @param $val
     * @param $data
     * @return \think\db\Query
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBarCodeAttr($val,$data){
        $items = Db::name('order_goods')->alias('a')->where('a.order_id',$data['id'])
            ->join('specs_goods_price b','a.item_id=b.gid and a.attr_ids=b.key')
            ->where('b.status',1)
            ->field('b.bar_code')
            ->order('a.id asc')
            ->select();
        $item = [];
        foreach ( $items as $k=>$v ){
            array_push($item,!empty($v['bar_code'])?$v['bar_code']:'无');
        }
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p> ".$value." </p>";
        }
        return $tt;
    }

    /***
     * 拼装条形码---退单
     * @param $val
     * @param $data
     * @return \think\db\Query
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBarCode2Attr($val,$data){
        $where = [];
//        $where[] = ['a.order_id','eq',$data['id']];
        $where[] = ['b.status','eq',1];
        $where[] = ['a.id','eq',$data['goods_id']];
        $where[] = ['refund_status','neq',0];


        $items = Db::name('order_goods')->alias('a')
            ->where($where)
//            ->where('a.order_id',$data['id'])
            ->join('specs_goods_price b','a.item_id=b.gid and a.attr_ids=b.key')
//            ->where('b.status',1)
            ->order('a.id asc')
            ->field('b.bar_code')
            ->select();
        $item = [];
        foreach ( $items as $k=>$v ){
            array_push($item,!empty($v['bar_code'])?$v['bar_code']:'无');
        }
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p> ".$value." </p>";
        }
        return $tt;
    }

    /***
     * 合并订单的支付状态与订单或者退单状态
     * @return \think\db\Query
     */
//    public function getMyStatusAttr( $val,$data ){
//        $pay_status = $data['pay_status'];  //支付状态
//        $order_status = $data['order_status'];  //订单状态
//        $refund_status = $data['refund_status'];    //退货状态
//        $status = [
//            1     =>'待支付',
//            2     =>'待发货',
//            3     =>'待收货',
//            4     =>'已完成',
//            5     =>'申请退款中',
//            6     =>'退款成功',
//            7     =>'退款拒绝',
//            8     =>'已取消'
//        ];
//        if( $pay_status == 0 ){
//            $myStatus = 1;
//        }else if( $pay_status == '-1' ){
//            $myStatus = 8;
//        }else if( $refund_status==0 || $refund_status==null ){
//            if( $order_status == 0 ){
//                $myStatus = 2;
//            }else if( $order_status == 1 ){
//                $myStatus = 3;
//            }else{
//                $myStatus = 4;
//            }
//        }else{
//            if( $refund_status == 1 ){
//                $myStatus = 5;
//            }else if( $refund_status == 2 ){
//                $myStatus = 6;
//            }else if( $refund_status == 5 ){
//                $myStatus = 7;
//            }else{
//                $myStatus = 4;
//            }
//        }
//        return  $status[$myStatus];
//    }

    /***
     * 合并订单的支付状态与订单或者退单状态
     * @return \think\db\Query
     */
    public function getMyStatusAttr( $val,$data ){
        $pay_status = $data['pay_status'];  //支付状态
        $order_status = $data['order_status'];  //订单状态
        $refund_status = $data['refund_status'];    //退货状态
        $status = [
            1     =>'待支付',
            2     =>'待发货',
            3     =>'待收货',
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
            $myStatus = 6;
        }else if( $order_status == 0 ){
            $myStatus = 2;
        }else if( $order_status == 1 ){
            $myStatus = 3;
        }else{
            $myStatus = 4;
        }
        return  $status[$myStatus];
    }

    /***
     * 获取订单是自营商品还是跨境购商品。是普通订单还是活动订单
     * @param $val
     * @param $data
     * @return \think\db\Query
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderTypeAttr($val,$data){
        $order_distinguish = $data['order_distinguish'];
        $status = [
            0     =>'普通订单',
            1     =>'拼团订单',
            2     =>'抢购订单',
            3     =>'限时抢购订单'
        ];
        $order_distinguish = empty($order_distinguish)?$status[0]:$status[$order_distinguish];
        if( $data['order_distinguish'] == 1 ){
            if( $data['assemble_status'] == 0 ){
                $order_distinguish .= '(拼团失败)';
            }
            if( $data['assemble_status'] == 1 ){
                $order_distinguish .= '(拼团成功)';
            }
            if( $data['assemble_status'] == 2 ){
                $order_distinguish .= '(拼团中)';
            }
        }
        $itemInfo = Db::name('order_goods')
            ->alias('a')
            ->join('item b','a.item_id=b.id')
            ->where('a.order_id',$data['id'])
            ->field('b.mold_id')
            ->find();
        $cateName = Db::name('item_type') ->where('id',$itemInfo['mold_id'])->value('title');
        $tt = '';
        $tt = '<p> '.$cateName.' </p>';
        $tt .= '<p> '.$order_distinguish.' </p>';
        if ( empty($data['coupon_id']) ) {
            $tt .= '未使用优惠券';
        }else{
            $coupon = Db::name('coupon')->where('id',$data['coupon_id'])->value('c_name');
            $info = (new CouponModel()) ->getCouponInfo(['id'=>$data['coupon_id']]);
            $rr = $info['c_use_price']==1?'在原价上使用':'在会员价上使用';
            $tt .= '<p> '.$info['c_name'].' </p>';
            $tt .= '<p> '.$rr.' </p>';
            $tt .= '<p> '.'减免'.$data['discount'].'元'.' </p>';
        }
        //查询是否使用折扣券
        $where = [];
        $where[] = ['order_id','eq',$data['id']];
        if ( $data['pay_status'] != 1 )
        {
            $where[] = ['type','neq',3];
        }else{
            $where[] = ['type','eq',1];
        }
        $money = Db::name('st_recharge_flow')->where($where)->sum('discount_price');
        $tt .= !$money?'<p>未使用折扣券</p>':'<p>折扣抵用:'.$money.'元</p>';

        //查询是否参与五一活动
        if ( $data['wuyi_ok'] == 1 )
        {
            $tt .= '<p>活动商品总金额：'.$data['wuyi_item_amount'].'</p>';
        }
        return $tt;
    }

    public function details(){
        return $this->table($this->table)->alias("a")->field("a.id,a.member_id,a.sn,a.type,a.pay_sn,a.pay_status,a.send_way,a.pay_way,a.paytime,a.sendtime,a.fixtime,a.order_status,a.is_online,m.nickname,m.mobile,a.postage,a.discount,a.amount,a.old_amount")->join("member m","a.member_id = m.id");
    }
//    public function getPayStatusAttr($val){
//        // 0=待付款\r\n1= 付款\r\n1= 取消订单
//        $data  = [
//            0=>"待付款",
//            1=>"已付款",
//            -1=>"取消订单",
//        ];
//        return $data[$val];
//    }

    public function getPostageAttr($val){
        if($val ==0){
            return "包邮";
        }
        return $val."元";
    }

    // 退款申请
    public function refund_apply($res=null){
        $where = [];
        $where[] = ['a.refund_type',"=",1];
        if( !empty($res['sn']) ){
            $where[] = ['a.sn','like','%'.$res['sn'].'%'];
        }
        if( !empty($res['shop_id']) ){
            $where[] = ['a.shop_id','eq',$res['shop_id']];
        }
        if( !empty($res['mobile']) ){
            $where[] = ['a.mobile|m.mobile','like','%'.$res['mobile'].'%'];
        }
        if( !empty($res['mobile']) ){
            $where[] = ['a.mobile|m.mobile','like','%'.$res['mobile'].'%'];
        }
        if( isset($res['refund_status']) && $res['refund_status'] != '' ){
            $where[] = ['o.status','eq',$res['refund_status']];
        }
        if( !empty($res['add_time']) ){
            $add_time = explode('-',$res['add_time']);
            $addWhere = strtotime($add_time[0].'-'.$add_time[1].'-'.$add_time[2].' 00:00:00').','.strtotime($add_time[3].'-'.$add_time[4].'-'.$add_time[5].' 23:59:59');
            $where[] = ['o.add_time','between',$addWhere];
        }
        if( !empty($res['paytime']) ){
            $add_time = explode('-',$res['paytime']);
            $addWhere = strtotime($add_time[0].'-'.$add_time[1].'-'.$add_time[2].' 00:00:00').','.strtotime($add_time[3].'-'.$add_time[4].'-'.$add_time[5].' 23:59:59');
            $where[] = ['a.paytime','between',$addWhere];
        }
        if( !empty($res['refund_time']) ){
            $add_time = explode('-',$res['refund_time']);
            $addWhere = strtotime($add_time[0].'-'.$add_time[1].'-'.$add_time[2].' 00:00:00').','.strtotime($add_time[3].'-'.$add_time[4].'-'.$add_time[5].' 23:59:59');
            $where[] = ['o.handle_time','between',$addWhere];
        }
        return $this->table($this->table)->alias("a")
            ->where($where)
            ->order('a.id','desc')
            ->join("order_refund_apply o","a.id = o.order_id")
            ->join('member m','a.member_id=m.id')
            ->field("a.id,a.order_distinguish,a.shop_id,a.event_id,o.goods_id,o.money,a.postage,a.shop_id as shop_name,a.add_time,a.paytime,a.amount,a.sn,a.pay_sn,a.pay_status,a.member_id,m.mobile,m.nickname,m.wechat_nickname,a.order_status,o.id as apply_id,o.status as refund_status,o.status as r_status,o.type as refund_type,a.pay_way,o.handle_time as refund_time");
    }

    /***
     * @param $val
     * @param $data
     * @return string
     */
    public function getShopNameAttr($val){
        return Db::name('shop')->where('id',$val)->value('name');
//        return  1;
    }

    //退货的信息
    public function getMemberInfo1Attr( $val,$data ){
        $paytime = $data['paytime'];
        if( empty($paytime) ){
            $pay = '未支付';
        }else if( strlen($paytime)==14 ){
            $pay = date('Y-m-d H:i:s',strtotime($paytime));
        }else{
            $pay = date('Y-m-d H:i:s',$paytime);
        }
        $refund_time = date('Y-m-d H:i:s',$data['refund_time']);
        $member = "<p>会员号：".$data['mobile']."</p>
				<p>会员名：".$data['wechat_nickname']."</p>
				<p>下单时间：".date('Y-m-d H:i:s',$data['add_time'])."</p>
				<p>支付时间：".$pay."</p>
				<p>处理时间：".$refund_time."</p>";
        return $member;
    }

//    public function getRefundStatusAttr($val){
//        if($val ==1){
//            return "申请中";
//        }else if($val ==2){
//            return "已同意";
//        }else if($val ==3){
//            return "已拒绝";
//        }
//    }

    // 退单 类型
    public function getRefundTypeAttr($val){
        if($val == 1){
            return "退款";
        }else if($val == 2){
            return "退款退货";
        }else if($val == 3){
            return "换货";
        }
    }
    public function getPaySnAttr($val){
        if($val){
            return $val;
        }
        return "暂无";
    }
//    public function getPayWayAttr($val){
//        $data = [
//            1=>"微信支付",
//            2=>"支付宝",
//            3=>"余额",
//            4=>"银行卡",
//            5=>"现金",
//            6=>"美团",
//            7=>"赠送",
//            8=>"门店自用",
//            9=>"兑换",
//            10=>"包月服务",
//            11=>"定制疗程",
//            99=>"管理员充值",
//        ];
//        return $data[$val];
//    }
    public function getPayTimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "未支付";
    }
    public function getTypeAttr($val){
        $data = [
            1=>"商品购买",
            2=>"预约服务",
            3=>"充值购卡",
            4=>"收银台收款",
            5=>"购买卷卡",
            6=>"兑换券",
            7=>"服务与商品订单",
        ];
        return $data[$val];
    }
    public function getSendWayAttr($val){
        $data = [
            0=>"门店快递",
            1=>"门店自提",
            2=>"总店发货",
        ];
        return $data[$val];
    }
    public function getSendTimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "未发货";
    }
    public function getFixTimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "未发货";
    }
//    public function getOrderStatusAttr($val){
//        $data = [
//            0=>"待发货",
//            1=>"待收货",
//            -1=>"申请退货",
//            2=>"交易完成",
//            -2=>"退货完成",
//            -5=>"申请退款",
//            -6=>"退款完成",
//            -7=>"门店取消",
//            -8=>"已取消",
//            8=>"配送中",
//            9=>"待处理",
//        ];
//        return $data[$val];
////        return $val;
//    }
    public function getCheckStatusAttr($val){
        $data = [
            0=>"未对账",
            1=>"已对账",
        ];
        return $data[$val]; 
    }
    public function getOvertimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "交易中";
    }
    /*public function getDeliverStatusAttr($val){
        if($val == 0){
            return "待发货";
        }else if( $val == 1){
            return "已发货";
        }
    }*/
    public function goodslist($order_id){
        return $this->table($this->table_goods)->alias("a")->where("a.order_id",$order_id)->join('order o','a.order_id=o.id')->field("a.id,a.subtitle,a.num,a.express_id,a.deliver_status,a.supplier,a.oprice,a.all_oprice,o.mobile,o.detail_address,o.realname,a.order_id");
    }

    /***
     * 获取是否使用了抵扣
     */
    public function getRechargeStatusAttr($val,$data)
    {
        $where = [];
        $where[] = ['order_id','eq',$data['id']];
        $where[] = ['type','neq',3];
        $list = Db::name('st_recharge_flow') ->where($where) ->select();
        $ratePrice = 0;  //抵扣的金额
        $refundPrice = 0;   //退单时退的抵扣金额
        foreach ( $list as $k=>$v )
        {
            if ( $v['type'] == 1 )
            {
                $ratePrice += $v['discount_price'];
            }
            if ( $v['type'] == 2 )
            {
                $refundPrice += $v['discount_price'];
            }
        }
        return ['ratePrice'=>$ratePrice,'refundPrice'=>$refundPrice];
    }

    /***
     * order_id订单ID,og_id 订单商品明细表ID，num退货数量
     * $renfundMoney 一共退款的金额
     * 退单时,退抵扣券
     */
    public function refundRecharge( $data,$renfundMoney )
    {
        $orderWhere = [];
        $orderWhere[] = ['order_id','in',$data['order_id']];
        $orderWhere[] = ['type','neq',3];
        $orderUse = Db::name('st_recharge_flow')->where($orderWhere) ->select();
        if( count($orderUse) == 0 )
        {
            return false;
        }
        $map = [];
        $map[] = ['id','in',$data['og_id']];
        $goods = $this ->table($this ->table_goods)->where($map)->field('id,order_id,num,real_price')->find();

        $allMoney = 0;  //总使用
        $useMoney = 0;  //总退款
        $surplusMoney = 0;  //剩余可退
        foreach ( $orderUse as $k=>$v )
        {
            if ( $v['type'] == 1 )
            {
                $allMoney += $v['discount_price'];
            }
            if ( $v['type'] == 2 )
            {
                $useMoney += $v['discount_price'];
            }
        }
        $surplusMoney = ($allMoney*100/100) - ($useMoney*100/100);
        if ( $surplusMoney == 0 )
        {
            return true;   //已经退完了
        }
        //该退金额（折扣抵用的金额）
        $shouldMoney = bcmul($renfundMoney,config('Recharge')['rate'],2);

        $money = $shouldMoney>=$surplusMoney?$surplusMoney:$shouldMoney;//最终退的金额

        //获取订单
        $lastOrder = $this ->table($this ->table)->where('id',$data['order_id'])->field('id,sn,member_id')->find();

        //获取用户最后一笔赠送的抵扣金额记录
        $lastRecharge = Db::name('st_recharge')->where('member_id',$lastOrder['member_id'])->order('id desc')->find();
        //获取总共可使用的抵扣金额
        $where = [];
        $where[] = ['member_id','eq',$lastOrder['member_id']];
        $where[] = ['expires_time','>=',time()];
        $where[] = ['remain_price','>',0];
        $allCanUseRe = Db::name('st_recharge')->where($where)->sum('remain_price');

        //将抵扣金额退款到最后的一笔
        Db::startTrans();
        try{
            //添加最后一笔的可使用的折扣金额
            $res = Db::name('st_recharge') ->where('id',$lastRecharge['id'])->setInc('remain_price',$money);

            if ( $res )
            {
                //添加st_recharge_flow记录表的退单记录
                $arr = [];
                $arr = [
                    'rec_id'    =>$lastRecharge['id'],
                    'order_id'    =>$lastOrder['id'],
                    'member_id'    =>$lastOrder['member_id'],
                    'discount_price'    =>$money,
                    'create_time'    =>time(),
                    'type'    =>2
                ];

                $res = Db::name('st_recharge_flow')->insert($arr);

                if ( $res )
                {
                    //添加financial_flow记录表的退单记录
                    $arr = [];
                    $arr = [
                        'member_id' =>$lastOrder['member_id'],
                        'flow_code' =>'DDXM'.date('Ymd').$this ->nonceStr(),
                        'order_code'   =>$lastOrder['sn'],
                        'flow_type' =>6,
                        'change_money'=>$money,
                        'pre_change_money'=>$allCanUseRe,
                        'after_change_money'=>bcadd($allCanUseRe,$money,2),
                        'pay_type'  =>0,
                        'money_type'    =>4,
                        'create_time'   =>time(),
                        'update_time'   =>time()
                    ];
                    $res = Db::name('financial_flow')->insert($arr);
                }
            }
            if ( !$res )
            {
                return false;
            }
            Db::commit();
        }catch (\Exception $e ){
            Db::rollback();
            return false;
        }
        return $res;
    }

    /***
     * 随机生成8位数
     * @return string
     */
    function nonceStr() {
        static $seed = array(0,1,2,3,4,5,6,7,8,9);
        $str = '';
        for($i=0;$i<8;$i++) {
            $rand = rand(0,count($seed)-1);
            $temp = $seed[$rand];
            $str .= $temp;
        }
        return $str;
    }
}
