<?php
// +----------------------------------------------------------------------
// | 商城订单模块
// +----------------------------------------------------------------------
namespace app\mall\controller;
use app\common\controller\Adminbase;
use app\common\model\TrackingMore;
use app\mall\model\order\OrderModel;
use app\mall\model\order\CommentModel;
use app\common\model\WxPayModel;
use app\mall\model\Adminlog;
use app\wxshop\wxpay\WxPayException;
use think\Db;
use think\db\Where;
use think\Exception;

class Order extends Adminbase{
    //订单列表
    public  function index(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            return $this->fetch();
        }else{
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            $model = new OrderModel();
            $where[] = ["is_online","=",1];//is_online 为线上商城
            $data = $model->index()->where($where)->page($page,$limit)->select();
            $count = $model->index()->where($where)->count();
            return json(["code" => 0, "count" => $count, "data" => $data]);
        }
    }
    //订单详情
    public function details(){
        if($this->request->isAjax()){
            $res = $this->request->post();
            $data = db::name("order_express")->where("id",intval($res['id']))->find();
            $extraInfo = ['destination_code'=>'US','lang'=>'cn'];
            $track = (new TrackingMore())->getRealtimeTrackingResults($data['code'],$data['sn'],$extraInfo);

            $traces = $track['data']['items'][0]['origin_info']['trackinfo'];
            $datas = [
                'meta'=>$track['meta'],
                'traces'=>empty($traces) ? [] : $traces,
                'LogisticCode'=>$data['title'],
                'express'=>$data['sn'],
            ];

            return_succ($datas,'查询成功');

//            $order = [
//                'sn'=>"",
//                "express_code"=>$data["sn"],
//                "code"=>$data['code']//$data["com"],
//            ];
//            $logisticResult=getOrderTracesByJson($order); 快递鸟的暂时不要了 2020-07-31
//            return  json_decode($logisticResult,true);
        }else{
            $res = $this->request->get();
            $model = new OrderModel();
            $data = $model->details($res)->where("a.id",intval($res['id']))->find();
            $express = db::name("item_company")->where("status",1)->field("id,title")->order("sort","asc")->select();
            $this->assign("express",$express);
            $this->assign("data",$data);
            return $this->fetch();
        }
    }
    //订单明细
    public function goodslist(){
        $res = $this->request->get();
        $order_id = $res['order_id'];
        if(!$order_id){
            return json(["code" => 0, "count" => 0, "data" => ""]);
        }
        $model = new OrderModel();
        $data = $model->goodslist($order_id)->select();
        foreach($data as $key => $val){
            if($val['supplier'] == null){
                $data[$key]['supplier_name'] = "待选择";
            }else if($val['supplier'] == 0){
                $data[$key]['supplier_name'] = "平台";
            }else{
                $data[$key]['supplier_name'] = db::name("admin")->where("userid",intval($val['supplier']))->value("nickname");
            }
            if($val['deliver_status'] == 0){
                $data[$key]['express'] ="未发货";
                $data[$key]['express_sn'] ="未发货";
            }else{
                $express = db::name("order_express")->where("order_id",$order_id)->where("id",$val['express_id'])->find();
                $data[$key]['express'] =$express['title'];
                $data[$key]['express_sn'] =$express['sn'];
                $data[$key]['order_express_id'] =$express['id'];
            }
        }
        $count = count($data);
        return json(["code" => 0, "count" => $count, "data" => $data]);
    }
    // 订单商品分配
    public function deliver(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            return $this->fetch();
        }else{
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            $model = new OrderModel();
            $where[] = ["a.is_online","=",1];//is_online 为线上商城
            $where[] = ['a.deliver_status',">",0];
            $data = $model->index()->where($where)->page($page,$limit)->select();
            /* echo $model*/
            $count = $model->index()->where($where)->count();
            return json(["code" => 0, "count" => $count, "data" => $data]);
        }
    }
    //分配供货商
    public function deliver_supplier(){
        $res = $this->request->post();
        $data = $res['data'];
        $supplier = $res['field']['supplier'];
        if(!isset($res['field']['supplier']) || empty($res['field']['supplier'])){
            return json(['result'=>false,'msg'=>"供货商参数错误","data"=>""]);
        }
        if(!$data){
            return json(['result'=>false,"msg"=>"数据错误","data"=>""]);
        }
        /* $order = db::name("order_goods")->Distinct(true)->alias("a")->where("a.supplier",0)->join("order o","a.order_id =o.id")->field("o.id")->select();*/
        $db = db::name("order_goods");
        $db->startTrans();
        $state = true;
        try{
            foreach($data as $key =>$val){
                $goods['supplier'] = $supplier;
                $res = db::name('order_goods')->where("id",$val['id'])->update($goods);
                if(!$res){
                    $state = false;
                    break;
                }
            }
            if(!$state){
                $db->rollback();
                return json(["result"=>false,"msg"=>"系统繁忙，请稍后再试","data"=>""]);
            }
            db::name("order")->where("id",$data[0]['order_id'])->setDec("deliver_status",count($data));
            $count = db::name("order_goods")->where("order_id",$data[0]['order_id'])->where("supplier",null)->count();
            $db->commit();
            return json(['result'=>true,"msg"=>"分配成功","data"=>$count]);
        }catch(\Exception $e){
            $error = $e->getMessage();
            $db->rollback();
            return json(["result"=>false,"msg"=>"系统错误","data"=>$error]);

        }
    }
    //需要分配供货商的商品列表
    public function deliver_list(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            $data = db::name("admin")->where("roleid",5)->field("userid as id,nickname")->select();
            $this->assign("data",$data);
            $this->assign("id",$res['id']);
            return $this->fetch();
        }else{
            $where = [];
            $model = new OrderModel();
            $where[] = ['order_id',"=",intval($res['id'])];
            $data = $model->order_goods()->where($where)->where("supplier",null)->select();
            $count = count($data);
            return json(["code" => 0, "count" => $count, "data" => $data]);
        }
        return $this->fetch();
    }

    //订单商品发货---列表
    public function deliver_order(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            $express = db::name("item_company")->where("status",1)->field("id,title")->order("sort","asc")->select();
            $this->assign("express",$express);
            $shop = db::name("shop")->field("id,name")->where('status',1)
                ->where('code','neq',0)->select();
            $this ->assign('shop',$shop);
            return $this->fetch();
        }else{
            $limit = $this->request->param('limit/d', 10);
            $page  = $this->request->param('page/d', 10);
            $res = $this ->request ->param();
            $where = [];
            if( !empty($res['sn']) ){
                $where[] = ['o.sn','like','%'.$res['sn'].'%'];
            }
            if( !empty($res['shop_id']) ){
                $where[] = ['o.shop_id','eq',$res['shop_id']];
            }
            if( !empty($res['mobile']) ){
                $where[] = ['o.mobile|m.mobile','like','%'.$res['mobile'].'%'];
            }
            if( !empty($res['subtitle']) ){
                $where[] = ['a.subtitle','like','%'.$res['subtitle'].'%'];
            }
            //物流异常
            if( isset($res['order_distinguish']) && $res['order_distinguish'] != '' ){
                $where[] = ['o.order_distinguish','eq',$res['order_distinguish']];
            }
            if( isset($res['cross_border']) && $res['cross_border'] != '' ){
                $where[] = ['o.cross_border','eq',$res['cross_border']];
            }
            if( isset($res['pay_status']) && $res['pay_status'] != '' ){
                $where[] = ['o.pay_status','eq',$res['pay_status']];
            }
            if( isset($res['pay_way']) && $res['pay_way'] != '' ){
                $where[] = ['o.pay_way','eq',$res['pay_way']];
            }
            if( !empty($res['add_time']) ){
                $add_time = explode('-',$res['add_time']);
                $addWhere = strtotime($add_time[0].'-'.$add_time[1].'-'.$add_time[2].' 00:00:00').','.strtotime($add_time[3].'-'.$add_time[4].'-'.$add_time[5].' 23:59:59');
                $where[] = ['o.add_time','between',$addWhere];
            }
            if( !empty($res['paytime']) ){
                $add_time = explode('-',$res['paytime']);
                $addWhere = strtotime($add_time[0].'-'.$add_time[1].'-'.$add_time[2].' 00:00:00').','.strtotime($add_time[3].'-'.$add_time[4].'-'.$add_time[5].' 23:59:59');
                $where[] = ['o.paytime','between',$addWhere];
            }
            if( isset($res['order_status']) && $res['order_status'] != '' ){
                if( $res['order_status'] == 1 ){
                    $where[] = ['o.pay_status','eq',0];
                }
                if( $res['order_status'] == 8 ){
                    $where[] = ['o.pay_status','eq',-1];
                }
                if( $res['order_status'] == 2 ){
                    $where[] = ['o.pay_status','eq',1];
                    $where[] = ['o.refund_status','eq',0];    //没有退单
                    $where[] = ['o.order_status','eq',0];    //待发货
                }
                if( $res['order_status'] == 3 ){
                    $where[] = ['o.pay_status','eq',1];
                    $where[] = ['o.refund_status','eq',0];    //没有退单
                    $where[] = ['o.order_status','eq',1];
                }
                if( $res['order_status'] == 4 ){
                    $where[] = ['o.pay_status','eq',1];
                    $where[] = ['o.refund_status','eq',0];    //没有退单
                    $where[] = ['o.order_status','not in','0,1']; //已完成
                }
                if( $res['order_status'] == 5 ){
                    $where[] = ['o.refund_status','eq',1];    //申请退单中
                }
            }
            $where[] = ["o.is_online","=",1];
            $model = new OrderModel();
            $data = $model->order_goods()->Distinct(true)->alias("a")
                ->join("order o","a.order_id =o.id")
                ->join("member m","o.member_id = m.id")
                ->where($where)
                ->field("o.id,o.wuyi_ok,o.wuyi_item_amount,o.assemble_status,o.cross_border,o.discount,o.c_receive_id,o.coupon_id,o.remarks,o.sn,o.postage,o.member_id,o.assemble_status,m.wechat_nickname,o.order_distinguish,o.refund_status,o.shop_id,o.add_time,o.type,o.pay_sn,o.pay_way,o.amount,o.order_status,o.pay_status,o.paytime,o.overtime,m.nickname,m.mobile,o.detail_address,o.mobile as mobile_address,o.realname")
                ->order('id desc')->page($page,$limit)->select()
                ->append(['message','item_list','price_list','cost_list','bar_code','my_status','order_type','member_info']);

            $count = $model->order_goods()->Distinct(true)->alias("a")
                ->join("order o","a.order_id =o.id")
                ->join("member m","o.member_id = m.id")
                ->where($where)
                ->count();
            return json(["code" => 0, "count" => $count, "data" => $data]);
        }
    }

    /***
     *编辑备注
     */
    public function update_remarks(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>0,'msg'=>'系统错误']);
        }
        $res = Db::name('order')->where('id',$data['id'])->setField('remarks',$data['value']);
        if( $res ){
            return json(['code'=>1,'msg'=>'编辑成功']);
        }else{
            return json(['code'=>0,'msg'=>'系统错误']);
        }
    }

    /***
     * 监听修改成本是否
     * @return int
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function update_oprice(){
        $data = $this ->request->param();
        $rule = '/(^[1-9]\d*(\.\d{1,2})?$)|(^0(\.\d{1,2})?$)/';
        $ruleResult = preg_match($rule,$data['value']);
        if (!$ruleResult) {
            return json(['code'=>0,'msg'=>'请输入保留两位小数的成本格式']);
        }
        $orderGoodsInfo = Db::name('order_goods') ->where('id',$data['id']) ->find();
        if( $orderGoodsInfo['deliver_status'] == 1 ){
            return json(['code'=>0,'msg'=>'商品已发货,禁止编辑成本']);
        }
        $arr = [];
        $arr = [
            'oprice'    =>$data['value'],
            'all_oprice'=>$data['value']*$orderGoodsInfo['num']
        ];
        $res = Db::name('order_goods') ->where('id',$data['id'])->update($arr);
        if( $res ){
            return json(['code'=>1,'msg'=>'修改成功']);
        }else{
            return json(['code'=>0,'msg'=>'编辑失败']);
        }
    }

    //订单商品发货----选择商品
    public function deliver_order_goods(){
        $res = $this->request->get();
        $order_id = intval($res['id']);
        $id = session('admin_user_auth')['uid'];
        $model = new OrderModel();
        $where = [];
        $where[] = ['order_id',"=",$order_id];
        $where[] = ["deliver_status","=",0];
        $where[] = ["refund_status","neq",2];
        $data = $model->order_goods()
            ->where($where)
            ->withAttr("deliver_status",function($value,$data){
                if($data['deliver_status'] == 0){
                    return "待发货";
                }else if($data['deliver_status'] == 1){
                    return "已发货";
                }
            })
            ->select();
        $count = $model->order_goods()->where($where)->count();
        return json(["code" => 0, "count" => $count, "data" => $data]);
    }

    //发货--填写物流单号后--点击确定
    public function deliver_order_express(){
        $res = $this->request->post()["field"];
        $id = $res['id'];//订单--商品 明细 ID
        $order_id = $res['order_id'];
        /*
        if(!isset($res['express']) || empty($res['express'])){
            return json(['result'=>false,'msg'=>"参数错误","data"=>""]);
        }
        if(!isset($res['sn']) || empty($res['sn'])){
            return json(['result'=>false,'msg'=>"参数错误","data"=>""]);
        }
        */
        //判断是否提交成本没有
        $where = [];
        $where[] = ['id','in',implode(',',$id)];
        $orderGoods  = Db::name('order_goods') ->where($where) ->select();
        $orderInfo = Db::name('order')->where('id',$res['order_id']) ->field('shop_id,sn,pay_way,sendtime')->find();
        //拼装成本数据
        $statisticsData = [];   //股东数据
        foreach ( $orderGoods as $k=>$v ){
            if( $v['oprice'] == '0.00' ){
                return json(['result'=>false,'msg'=>"请先设置成本再发货","data"=>""]);
            }
            $arr = [];
            $arr = [
                'order_id'  =>$res['order_id'],
                'shop_id'  =>$orderInfo['shop_id'],
                'order_sn'  =>$orderInfo['sn'],
                'type'  =>8,
                'data_type'  =>1,
                'pay_way'  =>$orderInfo['pay_way'],
                'price'  =>$v['oprice']*$v['num'],
                'create_time'  =>time(),
                'title'  =>'商品成本'
            ];
            array_push($statisticsData,$arr);
        }
        $express = db::name("item_company")->where("id",intval($res['express']))->find();
        // 启动事务
        Db::startTrans();
        try {
            foreach ( $id as $k=>$v ){
                $where  = [];
//                $where[] =['title',"=",$express['title']];
//                $where[] =['sn',"=",$res['sn']];
//                $where[] =['code',"=",$express['code']];
                $where[] =['order_id',"=",$order_id];
                $where[] =['order_goods_id',"=",$v];
                $old_express = Db::name("order_express")->where($where)->find();
                $expressData =  [
                    "title"=>!empty($express['title'])?$express['title']:'',
                    "sn"=>!empty($res['sn'])?$res['sn']:'',
                    "code"=>!empty($express['code'])?$express['code']:'',
//                    "add_time"=>time(),
//                    "operator"=>session('admin_user_auth')['uid'],
                    "order_id"=>$order_id,
                    "order_goods_id"=>$v,
                ];
                if( !$old_express ){
                    $expressData['add_time'] = time();
                    $expressData['operator'] = session('admin_user_auth')['uid'];
                    $express_id = Db::name("order_express")->insertGetId($expressData);
                }else{
                    $expressData['update_time'] = time();
                    $expressData['update_id'] = session('admin_user_auth')['uid'];
                    Db::name('order_express')->where($where)->update($expressData);
                    $express_id = $old_express['id'];
                }
                //修改商品副表的发货id
                $goods['express_id'] = $express_id;
                $goods['deliver_status'] = 1;
                $result = Db::name("order_goods")->where("id",intval($v))->update($goods);
            }
            if( !isset($res['express_type']) || ($res['express_type'] != 1) ){
                Db::name('statistics_log') ->insertAll($statisticsData);
            }
            //判断整个订单是否都已发货
            $mao = [];
            $mao[] = ['order_id','eq',$order_id];
            $mao[] = ['deliver_status','eq',0];
            $mao[] = ['status','eq',1];
            $mao[] = ['refund_status','eq',0];
            $count = Db::name("order_goods")->where($mao)->count();
            if($count==0){
                Db::name("order")->where("id",intval($order_id))->update(["order_status"=>1,'sendtime'=>time()]);
            }
            $data_count = Db::name("order_goods")->where("order_id",intval($order_id))
                ->where("supplier",intval(session('admin_user_auth')['uid']))->where("deliver_status",0)->count();

            //添加发货状态
            Db::name('order')->where("id",intval($order_id))->setField('sendtime',time());

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(["result"=>false,"msg"=>"系统错误","data"=>$e->getMessage()]);
        }
        return json(["result"=>true,"msg"=>"操作成功","data"=>$data_count]);
    }
    // 订单退货
    public function refund(){
        return $this->fetch();
    }

    //退货申请---列表
    public function refund_apply(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            $shop = db::name("shop")->field("id,name")->where('status',1)
                ->where('code','neq',0)->select();
            $this ->assign('shop',$shop);
            return $this->fetch();
        }else{
            $limit = $this->request->param('limit/d', 10);
            $page  = $this->request->param('page/d', 10);
            $res = $this ->request ->param();
            $model = new OrderModel();
            //排序规则， 注释掉
            $data = $model->refund_apply($res)
                ->page($page,$limit)
                ->order('id desc')
                ->group('o.id')
                ->select()
                ->append(['message2','item_list2','price_list2','cost_list2','bar_code2','my_status','order_type','member_info1','recharge_status']);
            $count  = $model->refund_apply($res)->count();
            return json(["code" => 0, "count" => $count, "data" => $data]);
        }
    }

    //退单详情
    public function refund_details(){
        $res = $this->request->post();
        if($res){
            $data = Db::name("order_refund_apply")
                ->alias('a')
                ->join('order b','a.order_id=b.id')
                ->where("a.id",intval($res['id']))
                ->field('a.*,b.postage,b.order_distinguish,b.event_id')
                ->find();

            if( $data["type"] == 1 ){
                $data["types"] = "商品退货";
            }else if( $data['type'] == 2 ){
                $data['types'] = "商品退款退货";
            }else if( $data['type'] == 3 ){
                $data['types'] = "商品换货";
            }else{
                $data['types'] = "数据错误";
            }
            if($data['pic']==""){
                $data['pic'] = [];
            }else{
                $data['pic'] = explode(",",$data['pic']);
            }
            $data['add_time'] = date("Y-m-d H:i:s",$data['add_time']);
            if($data){
                return json(["result"=>true,"msg"=>"获取成功","data"=>$data]);
            }else{
                return json(['result'=>false,"msg"=>"系统繁忙，请稍后再试",'data'=>""]);
            }
        }else{
            $res = $this->request->get();
            $type = intval($res['type']);

            $id = intval($res['id']);
            if($type == 1){
                $id  = db::name("order_refund_apply")->where("id",$id)->value("goods_id");

                $where = [];
                $where[] = ['id','eq',$id];
                $where[] = ['refund_status','neq',0];
                $data = db::name("order_goods")->where($where)
                    ->field("id,subtitle,real_price,num,num as num2,deliver_status,attr_ids,attr_name")
                    ->withAttr("deliver_status",function($value,$data){
                        if($data['deliver_status'] ==0){
                            return "待发货";
                        }
                        return "已发货";
                    })
                    ->withAttr("total",function($value,$data){
                        return $data['real_price'] * $data['num'];
                    })
                    ->select();
                $count = count($data);
            }else if($type ==2){
                $id  = Db::name("order_refund_apply")->where("id",$id)->value("goods_id");
                $where = [];
                $where[] = ['id','eq',$id];
                $where[] = ['refund_status','neq',0];
                $data = Db::name("order_goods")
                    ->where($where)
                    ->field("id,subtitle,real_price,num,num as num2,deliver_status,attr_ids,attr_name")
                    ->withAttr("deliver_status",function($value,$data){
                        if($data['deliver_status'] ==0){
                            return "待发货";
                        }
                        return "已发货";
                    })
                    ->withAttr("total",function($value,$data){
                        return $data['real_price'] * $data['num'];
                    })
                    ->select();
                $count = count($data);
            }else if($type ==3){
                $id  = db::name("order_refund_apply")->where("id",$id)->value("goods_id");
                $where = [];
                $where[] = ['id','eq',$id];
                $where[] = ['refund_status','neq',0];

                $data = db::name("order_goods")->where($where)
                    ->field("id,subtitle,real_price,num,num as num2,deliver_status,attr_ids,attr_name")
                    ->withAttr("deliver_status",function($value,$data){
                        if($data['deliver_status'] ==0){
                            return "待发货";
                        }
                        return "已发货";
                    })
                    ->withAttr("total",function($value,$data){
                        return $data['real_price'] * $data['num'];
                    })
                    ->select();
                $count = count($data);
            }

            //如果打包活动，处理价格
            if($res['order_distinguish'] == 5)
            {
                $pack = db('st_pack')->where(['id'=>$res['event_id'],'is_delete'=>0])->find();

                $data[0]['real_price'] = ($pack['p_condition1']/$pack['p_condition2'])*$data[0]['num'];
                $data[0]['total'] = $pack['p_condition1'];
            }

            return json(["code" => 0, "count" => $count, "data" => $data]);
        }
    }

    // 拒绝 退单
    public function return_goods(){
        $res = $this->request->post();
        $id = intval($res['id']);// 退款表 ID
        $value  = $res['value'];// 退货理由
        if($value == ''){
            return json(['code'=>100,'msg'=>'请输入拒绝理由！',"data"=>'']);
        }
        $order_refund_apply = \db('order_refund_apply')->where('id',$id)->find();
        if($order_refund_apply == false){
            return json(['code'=>100,'msg'=>'该退单不存在',"data"=>'']);
        }
        $order_id = $order_refund_apply['order_id'];
        $goods_id = $order_refund_apply['goods_id'];
        try{
            Db::startTrans();
//            退单状态0:正常    1退款中	2 退款成功 3 退款关闭 4 待寄件 5 退款拒绝 6 退款取消（用户手动取消退款）
            $refund_apply_status = [
                "status"=>3,
                "a_remarks"=>$value,
                "handle_time"=>time(),
                "operator_id"=>session('admin_user_auth')['uid'],
            ];
            $update_order = [
                "refund_status"=>5,
//                "order_status"=>-6,
            ];
            $update_order_goods = [
                "refund_status"=>5,
            ];
            //  更改对应的订单状态
            Db::name("order_refund_apply")->where("id",$order_refund_apply['id'])->update($refund_apply_status);
            //  更改对应的订单状态
            Db::name("order")->where("id",$order_id)->update($update_order);
            //更改对应的订单 明细 状态
            Db::name("order_goods")->where("id",$goods_id)->update($update_order_goods);
            Db::commit();
            return json(['code'=>200,"msg"=>"提交成功","data"=>'']);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            // 回滚事务
            Db::rollback();
            return json(['code'=>100,'msg'=>'服务器内部错误',"data"=>$error]);
        }
    }

    // 同意 退单

    /***
     * refund_order_postage     :1不退运费，2退运费
     * refund_order_type        :1全额退款，2部分退款
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agree_return(){

        $res = $this->request->post();
        $id = intval($res['id']);// 退款表 ID
        $order_refund_apply = Db::name('order_refund_apply')->where('id',$id)->find();
        if($order_refund_apply == false){
            return json(['code'=>100,'msg'=>'该退单不存在',"data"=>'']);
        }
        //1 为申请中
        //2 为 同 意
        //3 为拒绝
        $status = $order_refund_apply['status'];
        if($status !='1'){
            return json(['code'=>100,'msg'=>'该订单已处理！',"data"=>'']);
        }
        $order_id = $order_refund_apply['order_id'];
        $goods_id = $order_refund_apply['goods_id'];

        $order_sn = \db('order')->field('id,event_id,order_distinguish,member_id,postage,sn,amount,pay_way,shop_id,sendtime')->where('id',$order_id)->find();
        $member_info = Db::name('member') ->where('id',$order_sn['member_id'])->field('id,mobile')->find();
        $order_goods = Db::name('order_goods') ->where('id',$goods_id)->find();

        //打包活动处理下价格
        if($order_sn['order_distinguish'] == 5)
        {
            $pack = db('st_pack')->where(['id'=>$order_sn['event_id'],'is_delete'=>0])->find();

            $order_goods['real_price'] = round(($pack['p_condition1']/$pack['p_condition2'])*$order_goods['num']);

            //如果存在基数（10实际价格/3活动条件=3.3单件商品）
            $r_list = db('order_refund_apply')->where(['order_id'=>$order_sn['id']])->select();

            if(!empty($r_list))
            {

                $i = count($r_list);
                foreach ($r_list as $kk=>$vv)
                {
                    if($i==1)
                    {
                        //已退款的总金额
                        $r_true_money = array_column($r_list,'money')[0];
                        //$v = $pack['p_condition2']-count($r_list) == 0?1:count($r_list);
                        $r_false_money = $pack['p_condition1']-$r_true_money;//($pack['p_condition1']/$v)-$r_true_money;

                        if($r_true_money+$res['value']!=$pack['p_condition1'])
                        {
                            return_error('打包商品尾款必须:'.$r_false_money.'元');
                        }else{
                            $switch = true;
                        }
                    }

                    $vv['status'] == 2 ? $i-- : '';
                }
            }
        }

        $amount = $order_sn['amount'];      //订单金额,包含运费
        $postage = $order_sn['postage'];    //订单运费
        //判断是全退还是部分退
        if( $res['refund_order_type'] == 1 ){   //全退,则退款金额为订单总金额
            $money = $amount-$postage;   //全退金额，不包含总金额
        }else{
            $money = $res['value'];      //填写的退款金额，不包含运费
            //验证金额
            $rule = '/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
            $ruleResult = preg_match($rule,$money);
            if($ruleResult == false){
                return json(['code'=>100,'msg'=>'退款金额格式有误！',"data"=>'']);
            }
            if ( (bccomp($money,bcmul($order_goods['real_price'],$order_goods['num'],2)) == 1)  && !isset($switch))
            {
                return json(['code'=>100,'msg'=>'退款金额不能大于实付金额！',"data"=>'']);
            }
        }
        //判断是否退运费
        if( isset($res['refund_order_postage']) && $res['refund_order_postage'] == 2  ){
            //需要退运费
            $money = $money + $postage;
        }
        if ( bccomp($money,$amount) == 1 )
        {
            return json(['code'=>100,'msg'=>'退款金额不能够大于支付金额！',"data"=>'']);
        }
        try{
            Db::startTrans();
            if( $order_sn['pay_way'] == 1 ){
                //退到微信
                $wxpayModel = new WxPayModel();
                $data2 =[
                    'order_sn'=>$order_sn['sn'],//商品订单号
                    'refund_no'=>$order_refund_apply['sn'],//退单订单号
                    'total_fee'=>$order_sn['amount'],//商品支付的时候价格
                    'refund_fee'=>$money,
//                ',//商品退款金额  单位 分
                ];
                $da = $wxpayModel->refund($data2);
                if(isset($da['err_code'])){
                    return json(['code'=>100,'msg'=>'退款失败，请重试',"data"=>$da]);
                }
            }else if( $order_sn['pay_way'] == 3 )
            {
                //退到余额
                //查看是否使用了线上余额
                $log = Db::name('order_money_log') ->where('order_id',$order_sn['id'])->find();
                if( $log ){
                    //1：返用户余额:先反限时余额，再反线上余额，最后反普通余额
                    if( $money >= ($log['putong_money']+$log['xianshi_money']+$log['xianshang_money']) ){
                        //全退
                        $aMoney = $log['putong_money']+$log['xianshi_money'];
                        Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('money',$aMoney);
                        Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('online_money',$log['xianshang_money']);
                        Db::name('order_money_log')->where('id',$log['id'])->update(['putong_money'=>0,'xianshi_money'=>0,'xianshang_money'=>0]);	//
                    }else{
                        //先反限时余额，再反线上余额，最后反普通余额
                        if( $log['xianshi_money'] >= $money ){ //限时余额大于退款：只返限时余额
                            $t = $log['xianshi_money'] - $money;
                            Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('money',$money);   //返限时余额
                            Db::name('order_money_log')->where('id',$log['id'])->update(['xianshi_money'=>$t]);	//只用了限时余额
                        }else{
                            //限时余额不足，再扣线上余额
                            $lisMoney = $money - $log['xianshi_money']; //剩余该返的金额：返线上余额和普通余额
                            if( $log['xianshang_money'] >= $lisMoney ){ //线上余额大于等于 剩余的金额
                                //剩余的金额返线上余额
                                Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('money',$log['xianshi_money']);//返限时余额
                                Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('online_money',$lisMoney);//返线上余额
                                $log_update = [];
                                $log_update = [
                                    'xianshi_money'		=>0,
                                    'xianshang_money'	=> $log['xianshang_money'] - $money + $log['xianshi_money']
                                ];
                                Db::name('order_money_log')->where('id',$log['id'])->update($log_update);	//限时余额退完,线上余额退一部分
                            }else{
                                //限时余额和线上余额都不足，还要返普通余额
                                $xiansMoneyAndPutong = $money - $log['xianshang_money'];    //
                                Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('money',$xiansMoneyAndPutong);//返限时余额与普通余额
                                Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('online_money',$log['xianshang_money']);//返线上余额
                                $log_update = [];
                                $log_update = [
                                    'xianshi_money'		=>0,
                                    'xianshang_money'	=>0,
                                    'putong_money'	=> $log['putong_money'] -($money - $log['xianshang_money']-$log['xianshi_money'])
                                ];
                                Db::name('order_money_log')->where('id',$log['id'])->update($log_update);	//限时余额退完,线上余额退完，普通余额退一部分
                            }
                        }
                    }
                }else{
                    Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('money',$money);
                }

                //2：增加用户的余额使用记录
                $detailsData = [];
                $detailsData = [
                    'member_id' =>$member_info['id'],
                    'mobile' =>$member_info['mobile'],
                    'remarks' =>'小程序退单',
                    'reason' =>'小程序购买商品',
                    'addtime' =>time(),
                    'amount' =>'-'.$money,
                    'type' =>2,
                    'order_id' =>$order_sn['id'],
                ];
                Db::name('member_details')->insert($detailsData);
                //3：判断是否是使用的限时余额
                $expireLog = Db::name('member_expire_log')
                    ->where('member_id',$order_sn['member_id'])
                    ->where('order_id',$order_sn['id'])
                    ->select();
                if( count($expireLog) > 0 ){
                    $money1 = $money;       //单独赋值一个money1主要是为了返回限时余额使用,$money下面的操作还得用
                    foreach ( $expireLog as $k2=>$v2 ){
                        //1：给member_expire_log表新增一个负的退款记录
                        if( $money1 >= 0 ){
                            $arr = [];
                            $expirePrice = 0;
                            if( $money1 >= $v2['price'] ){
                                $expirePrice = $v2['price'];
                            }else{
                                $expirePrice = $money1;
                            }
                            $arr = [
                                'member_id' =>$order_sn['member_id'],
                                'order_id' =>$order_sn['id'],
                                'price' =>'-'.$expirePrice,
                                'money_expire_id' =>$v2['money_expire_id'],
                                'order_sn' =>$v2['order_sn'],
                                'create_time' =>time(),
                                'reason' =>'退款:小程序退单'
                            ];
                            Db::name('member_expire_log')->insert($arr);
                            //2：给member_money_expire表减少使用金额
                            Db::name('member_money_expire') ->where('id',$v2['money_expire_id'])->setDec('use_price',$expirePrice);
                            //判断限时余额是否过期、如果过期则生成一笔正的余额消耗
//                            $member_money_expire = Db::name('member_money_expire')->where('id',$v2['money_expire_id'])->find();
//                            if( $member_money_expire['status'] == 2 || (time()>$member_money_expire['expire_time']) ){
//                                $aee = [];
//                                $sn = 'WME'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$order_sn['shop_id'];
//                                $aee = [
//                                    'order_id'  =>$order_sn['id'],
//                                    'shop_id'   =>$order_sn['shop_id'],
//                                    'order_sn'  =>$sn,
//                                    'type'      =>4,
//                                    'data_type' =>1,
//                                    'pay_way'   =>3,
//                                    'price'   =>$expirePrice,
//                                    'create_time'   =>time(),
//                                    'title'   =>'退款时限时余额到期',
//                                ];
//                                Db::name('statistics_log')->insert($aee);
//                                $ar = [];
//                                $ar = [
//                                    'money_expire_id'    =>$v2['money_expire_id'],
//                                    'member_id'    =>$v2['member_id'],
//                                    'shop_id'    =>$order_sn['shop_id'],
//                                    'sn'    =>$sn,
//                                    'price'    =>$expirePrice,
//                                    'craete_time'    =>time(),
//                                    'remarks'   =>'限时余额退款时已过期'
//                                ];
//                                Db::name('money_expire_log')->insert($ar);
//                                //1：减余额
//                                $res = Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setDec('money',$expirePrice);
//                                //2：增加用户的余额使用记录
//                                $detailsData = [];
//                                $detailsData = [
//                                    'member_id' =>$member_info['id'],
//                                    'mobile' =>$member_info['mobile'],
//                                    'remarks' =>'小程序退单时限时余额过期',
//                                    'reason' =>'小程序退单时限时余额过期',
//                                    'addtime' =>time(),
//                                    'amount' =>$expirePrice,
//                                    'type' =>2,
//                                    'order_id' =>$order_sn['id'],
//                                ];
//                                Db::name('member_details')->insert($detailsData);
//                            }
                            //添加用户使用详情
                            $money1 -= $expirePrice;
                        }
                    }
//                    if($money1 > 0){
//                        //1：返用户余额
//                        Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('money',$money1);
//                        //2：增加用户的余额使用记录
//                        $detailsData = [];
//                        $detailsData = [
//                            'member_id' =>$member_info['id'],
//                            'mobile' =>$member_info['mobile'],
//                            'remarks' =>'小程序退单',
//                            'reason' =>'小程序购买商品',
//                            'addtime' =>time(),
//                            'amount' =>'-'.$money1,
//                            'type' =>2,
//                            'order_id' =>$order_sn['id'],
//                        ];
//                        Db::name('member_details')->insert($detailsData);
//                    }
                }
//                 else{
//                    //1：返用户余额
//                    Db::name('member_money') ->where('member_id',$order_sn['member_id'])->setInc('money',$money);
//                    //2：增加用户的余额使用记录
//                    $detailsData = [];
//                    $detailsData = [
//                        'member_id' =>$member_info['id'],
//                        'mobile' =>$member_info['mobile'],
//                        'remarks' =>'小程序退单',
//                        'reason' =>'小程序购买商品',
//                        'addtime' =>time(),
//                        'amount' =>'-'.$money,
//                        'type' =>2,
//                        'order_id' =>$order_sn['id'],
//                    ];
//                    Db::name('member_details')->insert($detailsData);
//                }
            }else{
                return json(['code'=>100,'msg'=>'退款失败,当前支付方式不是微信也不是余额',"data"=>'']);
            }

            //判断是否退抵扣金额
            if ( $res['refund_recharge_type'] == 2 )
            {
                $arr = ['og_id'=>$goods_id,'order_id'=>$order_id];
                $resRefund = ( new OrderModel() ) ->refundRecharge($arr,$money);

                if ( !$resRefund )
                {
                    return json(['code'=>100,'msg'=>'退款失败，请重试',"data"=>'']);
                }
            }

//            退单状态0:正常    1退款中	2 退款成功 3 退款关闭 4 待寄件 5 退款拒绝 6 退款取消（用户手动取消退款）
            $refund_apply_status = [
                "status"=>2,
                "handle_time"=>time(),
                "money"=>$money,
                "operator_id"=>session('admin_user_auth')['uid'],
            ];
//            退单状态0:正常    1退款中 2 退款成功 3 退款关闭 4 待寄件 5 退款拒绝 6 退款取消（用户手动取消退款） 7 退货寄件中

            $update_order_goods = [
                "refund_status"=>2,
                "status"=>2,
            ];

            //  更改对应的订单状态
            Db::name("order_refund_apply")->where("id",$order_refund_apply['id'])->update($refund_apply_status);

            //  更改对应的订单状态
            $update_order = [
                "refund_status"=>2,
                "order_status"=>-6,
            ];
            $orderWhere = [];
            $orderWhere[] = ['refund_status','eq',0];
            $orderWhere[] = ['order_id','eq',$order_id];
            $order_update = Db::name('order_goods')->where($orderWhere)->select();  //查询是否全部都退了
            if( count($order_update) == 0 ){
                //表示全部都退了
                Db::name("order")->where("id",$order_id)->update($update_order);
            }
            //更改对应的订单 明细 状态
            Db::name("order_goods")->where("id",$goods_id)->update($update_order_goods);
            //根据$goods_id退回商品库存
            $goodsList = Db::name('order_goods') ->where('id',$goods_id)->field('num,item_id,attr_ids')->find();
            $map = [];
            $map[] = ['gid','eq',$goodsList['item_id']];
            $map[] = ['key','eq',empty($goodsList['attr_ids'])?'':$goodsList['attr_ids']];
            $map[] = ['status','eq',1];
            $lt = Db::name('specs_goods_price') ->where($map) ->find();
            if( $lt['store'] != '-1' ){
                Db::name('specs_goods_price') ->where($map) ->setInc('store',$goodsList['num']);
            }

            //判断整个订单是否都已发货
            $mao = [];
            $mao[] = ['order_id','eq',$order_id];
            $mao[] = ['deliver_status','eq',0];
            $mao[] = ['status','eq',1];
            $mao[] = ['refund_status','in','0,5'];
            $count = Db::name("order_goods")->where($mao)->count();
            if($count==0){
                Db::name("order")->where("id",intval($order_id))->update(["order_status"=>1]);
            }
            //根据goods_id改变分销订单状态
            $orderRetailCount = Db::name('order_retail')->where('order_id',$order_sn['id'])->select();
            if( count($orderRetailCount) >0 ){
                foreach ( $orderRetailCount as $k=>$v ){
                    if( $v['order_goods_id'] != 0 ){
                        //根据商品明细退
                        Db::name('order_retail')->where(['order_id'=>$order_sn['id']])->where('order_goods_id',$goods_id)->setField('status',2);
                    }else{
                        //之前的分销未按照商品明细，则全部退
                        Db::name('order_retail')->where('id',$orderRetailCount['id'])->setField('status',2);
                    }
                }
            }

            //股东数据
            $statisticsInfo = [];//具体股东数据
            $statisticsInfo = [
                'order_id'  =>$order_id,
                'shop_id'   =>$order_sn['shop_id'],
                'order_sn'   =>$order_sn['sn'],
//                'type'   =>4,
                'data_type'   =>2,
//                'pay_way'   =>1,
                'price'   =>'-'.$money,
                'create_time'   =>time(),
                'title'   =>'小程序购买商品',
            ];
            $statisticsData = [];//总的股东数据
            //1:余额充值,2:购卡,3:消费收款,4:余额消耗,5消费消耗,6商品外包分润,7推拿外包分润,8商品成本,9营业费用,10外包商品成本'
            //A先加入订单股东数据
            if( $order_sn['pay_way'] == 1 ){
                //微信
                $statisticsInfo['pay_way'] = 1;
                $statisticsInfo['type'] = 3;
                array_push($statisticsData,$statisticsInfo);
                $statisticsInfo['type'] = 5;
                array_push($statisticsData,$statisticsInfo);
            }else{
                //余额
                $statisticsInfo['pay_way'] = 3;
                $statisticsInfo['type'] = 4;
                array_push($statisticsData,$statisticsInfo);
            }
            //B加入订单商品成本股东数据$order_refund_apply
            $oprice = Db::name('order_goods')->where('id',$order_refund_apply['goods_id'])->find();
            if( $oprice['deliver_status'] == 1 ){
                $statisticsInfo['type'] = 8;
                $statisticsInfo['price'] = '-'.$oprice['all_oprice'];
                array_push($statisticsData,$statisticsInfo);
            }
            Db::name('statistics_log') ->insertAll($statisticsData);

            $item_id = 0;
            //退数量
            $json_tab = $res['json_tab'];
            $tab =json_decode($json_tab,true);
            for($index = 0;$index<count($tab);$index++){
                $tt = $tab[$index];
                $num2 = $tt['num2'];//退货数量
                $num = $tt['num'];//购买 数量

                if($num2 != 0){// 退货数量为 0  表示 不退商品

                    $id = $tt['id'];//订单--》对应的商品ID
                    if($item_id == 0){
                        $item_id = Db::name('order_goods')->where('id',$id)->value('item_id');
                    }
                    if($num2>$num){
                        return json(['code'=>100,'msg'=>'退货数量不能够大于购买数量！',"data"=>'']);
                    }

                    if(empty($attr_ids)){//如果没有规格

                        //获取当前商品的库存
                        $store =  Db::name('specs_goods_price')
                            ->where('gid',$item_id)
                            ->where('status','1')->value('store');


                        if($store!=-1){// 只有 是 非 无限制  才可以退


                            $map = [];
                            $map[] = ['gid','eq',$item_id];
                            $map[] = ['status','eq',1];
                            Db::name('specs_goods_price')
                                ->where($map)
                                ->setInc('store',$num2);
//                            dump($dd);
//                            exit;
                        }
                    }else{

                        //获取当前商品的库存
                        $store =  Db::name('specs_goods_price')
                            ->where('gid',$item_id)
                            ->where('key',$attr_ids)
                            ->where('status','1')->value('store');

                        if($store!=-1){// 只有 是 非 无限制  才可以退

                            Db::name('specs_goods_price')
                                ->where('gid',$item_id)
                                ->where('key',$attr_ids)
                                ->where('status','1')
                                ->setInc('store',$num2);
                        }
                    }

                    $re_goods=[
                        'refund_id'=>$order_refund_apply['id'],
                        'og_id'=>$id,
                        'r_num'=>$num2,
                        'status'=>1,
                    ];
                    Db::name('order_refund_goods')->insert($re_goods);
                    Db::name('order_goods')->where('id',$id)->setInc('refund',$num2);
                }
            }
            Db::commit();
            return json(['code'=>200,"msg"=>"提交成功","data"=>'']);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            // 回滚事务
            Db::rollback();
            return json(['code'=>100,'msg'=>'退款失败，请重试',"data"=>$error]);
        }
    }
    public function comment(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            return $this->fetch();
        }else{
            $limit = $this->request->param('limit/d', 10);
            $page  = $this->request->param('page/d', 10);
            $where = [];
            $model = new CommentModel();
            $data = $model->index()->where($where)->page($page,$limit)->order("add_time","desc")->select();
            $count = $model->index()->where($where)->count();
            return json(["code" => 0, "count" => $count, "data" => $data]);
        }
    }
    public function comment_edit(){
        $res = $this->request->post();
        if(!isset($res['id']) || empty($res['id'])){
            return json(["result"=>false,'msg'=>"系统繁忙，请稍后再试","data"=>""]);
        }
        if(!isset($res['status']) || empty($res['status'])){
            return json(['result'=>false,'msg'=>'系统繁忙，请稍后再试',"data"=>""]);
        }
        if(isset($res['value']) && !empty($res['value'])){
            $remark = $res['value'];
        }else{
            $remark = "";
        }
        $comment = [
            "remark"=>$remark,
            "status"=>intval($res['status']),
            "operator"=>Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
            "operator_id"=>session("admin_user_auth")['uid'],
            "operator_time"=>time(),
        ];
        $result = db::name("comment")->where("id",intval($res['id']))->update($comment);
        if($result){
            return json(['result'=>true,"msg"=>"审核成功","data"=>""]);
        }else{
            return json(['result'=>false,"msg"=>'系统繁忙，请稍后再试',"data"=>""]);
        }
    }
    public function comment_add(){
        if($this->request->isAjax()){
            $res = $this->request->post();
            if(isset($res['images']) && !empty($res['images'])){
                $image = $res['images'];
                $pic = "";
                foreach( $image as $key => $val ){
                    if($key ==0){
                        $pic .= $val;
                    }else{
                        $pic .= ",".$val;
                    }
                }
            }else{
                $pic = '';
            }
            $comment = [
                "item_id"=>intval($res['item']),
                "level"  =>intval($res['rate']),
                "comment"=>$res['comment'],
                "pic"    =>$pic,
                "member_id"=>0,
                "status" => 1,
                "add_time"=>time(),
                "operator"=>Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
                "operator_id"=>session("admin_user_auth")['uid'],
                "operator_time"=>time(),
            ];
            $result = db::name("comment")->insert($comment);
            if($result){
                return json(['result'=>true,"msg"=>'添加成功','data'=>""]);
            }else{
                return json(['result'=>false,"msg"=>'系统繁忙,请稍后再试',"data"=>'']);
            }
        }else{
            return $this->fetch();
        }
    }
    public function comment_item_list(){
        $res = $this->request->get();
        $limit = $this->request->param('limit/d', 10);
        $page  = $this->request->param('page/d', 10);
        $where = [];
        if(isset($res['data']['name']) && !empty($res['data']['name'])){
            $where[] = ['title|bar_code',"like","%".$res['data']['name']."%"];
        }
        $where[] = ['item_type',"=",1];
        $where[] = ['status',"=",1];
        $data = db::name("item")->where($where)->page($page,$limit)->select();
        $count = db::name("item")->where($where)->count();
        return json(['code'=>0,"data"=>$data,"count"=>$count]);
    }

    /***
     * 会员信息
     */
    public function memberInfo(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>0,'msg'=>"获取失败",'data'=>'']);
        }
        $member = Db::name('member') ->where('id',$data['id'])->find();
        if( $member['attestation'] == 1 ){
            $member['attestation_info'] = Db::name('member_attestation') ->where('member_id',$data['id'])->find();
            $member['attestation_info']['backl'] = $member['attestation_info']['back'];
        }else{
            $member['attestation_info'] = [];
        }
        return json(['code'=>1,'msg'=>"获取成功",'data'=>$member]);
    }
}