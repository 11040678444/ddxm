<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\Index\Controller\Statistics as IndexStatistics;
use think\Db;


/**
统计报表
 */
class Statistics extends Adminbase
{
    /***
     * 充值统计
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cz(){
        $start_time = $this ->request->param('start_time')?$this ->request->param('start_time'):"2000-01-01 00:00:00";
        $end_time = $this ->request->param('end_time')?$this ->request->param('end_time'):date('Y-m-d')." 23:59:59";
        $shop_id = $this ->request->param('shop_id');
        $time = strtotime($start_time).','.strtotime($end_time);
        if( $shop_id ){
            $where[] = ['shop_id','eq',$shop_id];
        }
        $where[] = ['create_time','between',$time];
        $all_price = Db::name('member_recharge_log')->where($where)->sum('price');  //总充值

        $where[] = ['price','<',0];
        $price = Db::name('member_recharge_log')->where($where)->sum('price');  //退款金额
        $res = [];
        $res = ['all_price'=>$all_price,'price'=>$price];
        $this ->assign('res',$res);
        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);

        $this ->assign('data',$this ->request->param());
        return $this->fetch();
    }

    /***
     * 会员统计
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function member(){
        $data = $this ->request ->param();
        if( !empty($data['shop_id']) ){
            $shop_id = $data['shop_id'];
        }
        $start_time = $this ->request->param('start_time')?$this ->request->param('start_time'):date('Y-m-d')." 00:00:00";
        $end_time = $this ->request->param('end_time')?$this ->request->param('end_time'):date('Y-m-d'." 23:59:59");
        if( !$end_time || !$start_time ){
            return json(['code'=>-3,'msg'=>'请选择起止时间']);
        }
        $time = strtotime($start_time).','.strtotime($end_time);
        //总会员，与时间无关
        if( !empty($data['shop_id']) ){
            $shop_code = Db::name('shop')->where('id',$shop_id)->value('code');
            $allWhere[] = ['shop_code','eq',$shop_code];
        }
//        $allWhere[] = ['shop_code','eq',$shop_code];
        $allWhere[] = ['status','eq',1];
        $all_total = Db::name('member')->where($allWhere)->count();	//总会员人数

        //消费会员总
        if( !empty($data['shop_id']) ){
            $oWhere[] = ['shop_id','eq',$shop_id];
        }
        $oWhere[] = ['add_time','between',$time];
        $oWhere[] = ['member_id','neq',0];
        $xiao_total = Db::name('order')->where($oWhere)->field('member_id')->select();	//消费总订单
        $xiao_total = $this ->array_unset_tt($xiao_total,'member_id');  //去重
        $xiao_total = count($xiao_total);       //消费总会员

        //新客会员
        if( !empty($data['shop_id']) ){
            $oWhere[] = ['a.shop_id','eq',$shop_id];
        }
        $oWhere[] = ['a.add_time','between',$time];
        $oWhere[] = ['a.member_id','neq',0];
        $oWhere[] = ['b.regtime','between',$time];
        $new_member = Db::name('order')
            ->alias('a')
            ->where($oWhere)
            ->join('member b','a.member_id=b.id','left')
            ->field('a.member_id')
            ->select();	//消费总订单
        $new_member = $this ->array_unset_tt($new_member,'member_id');  //去重
        $new_member = count($new_member);       //新客会员

        //散客会员
        if( !empty($data['shop_id']) ){
            $snWhere[] = ['shop_id','eq',$shop_id];
        }
        $snWhere[] = ['add_time','between',$time];
        $snWhere[] = ['member_id','eq',0];
        $sanCount = Db::name('order')->where($snWhere)->count();	//散客总人数
        $result = ['all_total'=>$all_total,'new_member'=>$new_member,'xiao_total'=>$xiao_total,'sanCount'=>$sanCount];
        $this ->assign('res',$result);

        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);

        $this ->assign('data',$this ->request->param());
        return $this->fetch();
    }


    /****
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function expenditure(){
        if ($this->request->isAjax()) {
            $data = $this ->request ->param();
            if( !empty($data['type_id']) ){
                $typeWhere[] = ['id','eq',$data['type_id']];
            }
            $typeWhere[] = ['delete_time','<=',0];
            $list = Db::name('expenditure_types')->where($typeWhere)->field('title,id as type_id')->order('sort asc')->select();   //所以类型

            foreach ($list as $k=>$v){
                $list[$k]['price'] = 0;
            }

            if( !empty($data['start_time']) && !empty($data['end_time']) ){
                $time = strtotime($data['start_time']).','.strtotime($data['end_time']);
                $dataWhere[] = ['create_time','between',$time];
            }
            if( !empty($data['type_id']) ){
                $dataWhere[] = ['type_id','eq',$data['type_id']];
            }
            $dataWhere[] = ['status','eq',1];
            if( !empty($data['shop_id']) ){
                $dataWhere[] = ['shop_id','eq',$data['shop_id']];
            }
            $info = Db::name('expenditure')->where($dataWhere)->field('type_id,price')->select();       //全部记录

            foreach ($list as $key=>$val){
                foreach ($info as $k=>$v){
                    if( $val['type_id'] == $v['type_id'] ){
                        $list[$key]['price'] += $v['price'];
                    }
                }
            }
            //计算总金额
            $all_price = 0;
            foreach ($list as $v){
                $all_price += $v['price'];
            }
            $total = count($list);
            $result = array("code" => 0, 'all_price'=>$all_price,"count" => $total, "data" => $list);
            return json($result);
        }
        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);

        $typeWhere[] = ['delete_time','<=',0];
        $type = Db::name('expenditure_types')->where($typeWhere)->field('title,id as type_id')->order('sort asc')->select();   //所以类型
        $this ->assign('type',$type);
        return $this->fetch();
    }

    /****
     * 服务统计
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function service(){
        if ($this->request->isAjax()) {
            $data = $this ->request->param();
            if( !empty($data['start_time']) && !empty($data['end_time']) ){
                $time = strtotime($data['start_time']).','.strtotime($data['end_time']);
                $where[] = ['b.add_time','between',$time];
            }
            if( !empty($data['service_id']) ){
                $where[] = ['a.service_id','eq',$data['service_id']];
            }
            if( !empty($data['shop_id']) ){
                $where[] = ['b.shop_id','eq',$data['shop_id']];
            }
            //先查询出这段时间卖出的多有的服务项目
            $list = Db::name('service_goods')
                ->where($where)
                ->alias('a')
                ->join('order b','a.order_id=b.id')
                ->field('a.id,a.workerid,a.num,a.name,a.real_price,a.service_id,a.service_name,refund')
                ->select();     //订单购买的数据
            foreach ($list as $k=>$v){
                if( $v['num'] == $v['refund'] ){
                    unset($list[$k]);
                }else{
                    $list[$k]['num'] = $v['num'] - $v['refund'];
                }
                unset($list[$k]['refund']);
            }
            //耗卡显示的数据
            $consumptionWhere = [];
            $consumptionWhere[] = ['time','between',$time];
            if( !empty($data['shop_id']) ){
                $consumptionWhere[] = ['shop_id','eq',$data['shop_id']];
            }
            $list1 = DB::name('ticket_consumption')->where($consumptionWhere)
                ->field('service_id,waiter_id as workerid,num,waiter as name,price as real_price,service_name')
                ->select();
            $info = [];

            foreach($list as $k=>$v)
            {
                if( isset($info[$v['workerid'].'-'.$v['service_id']]) ){
                    $info[$v['workerid'].'-'.$v['service_id']][] = $v;
                }else{
                    $info[$v['workerid'].'-'.$v['service_id']][] = $v;
                }
            }
            $info1 = [];
            foreach($list1 as $k=>$v)
            {
                if( isset($info1[$v['workerid'].'-'.$v['service_id']]) ){
                    $info1[$v['workerid'].'-'.$v['service_id']][] = $v;
                }else{
                    $info1[$v['workerid'].'-'.$v['service_id']][] = $v;
                }
            }

            if( count($info)>0 ){
                foreach ($info as $k=>$v){
                    $info[$k]['all'] = 0;
                    foreach ($v as $k1=>$v1){
                        $info[$k]['all'] += $v1['num'];
                    }
                }
            }
            if( count($info1)>0 ){
                foreach ($info1 as $k=>$v){
                    $info1[$k]['all'] = 0;
                    foreach ($v as $k1=>$v1){
                        $info1[$k]['all'] += $v1['num'];
                    }
                }
            }

            $result = [];
            if( count($info) <=0 && count($info1)>0 ){
                $result = $info1;
            }else if( count($info)>0 && count($info1)<=0 ){
                $result = $info;
            }else if( count($info)>0 && count($info1)>0 ){
                foreach ($info as $k=>$v){
                    if( isset($result[$k]) ){
                        $result[$k]['all'] = $result[$k]['all'] + $v['all'];
                    }else{
                        $result[$k] = $v;
                    }
                }
                foreach ($info1 as $k=>$v){
                    if( isset($result[$k]) ){
                        $result[$k]['all'] = $result[$k]['all'] + $v['all'];
                    }else{
                        $result[$k] = $v;
                    }
                }
            }
            $res = [];
            foreach ($result as $k=>$v){
                $arr = array(
                    'workerid'  =>$v['0']['workerid'],
                    'name'  =>$v['0']['name'],
                    'service_id'  =>$v['0']['service_id'],
                    'service_name'  =>$v['0']['service_name'],
                    'num'  =>$v['all']
                );
                array_push($res,$arr);
            }

            $last_names = array_column($res,'num');
            array_multisort($last_names,SORT_DESC,$res);

            foreach ($res as $k=>$v){
                $res[$k]['start_time'] = $data['start_time'];
                $res[$k]['end_time'] = $data['end_time'];
            }

            if( !empty($data['waiter_id']) ){
                foreach ($res as $k=>$v){
                    if( $v['workerid'] != $data['waiter_id'] ){
                        unset($res[$k]);
                    }
                }
            }
            $info = [];
            foreach ($res as $k=>$v){
                array_push($info,$v);
            }
            $total = count($info);
            $result = array("code" => 0,"count" => $total, "data" => $info);
            return json($result);
        }
        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);

        return $this->fetch();
    }

    /****
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function select(){
        $shop_id = $this ->request ->param('shop_id');
        if( !$shop_id ){
            return json(['code'=>2,'msg'=>'未选择门店id']);
        }
        $where[] = ['sid','eq',$shop_id];
        $where[] = ['status','eq',1];
        $list = Db::name('shop_worker')->where($where)->field('id,name')->select();     //服务人员
        $service = Db::name('service_price')->alias('a')
            ->join('service b','a.service_id=b.id')
            ->where(['a.shop_id'=>$shop_id,'a.level_id'=>1,'a.status'=>1])
            ->field('b.id as service_id,b.sname')
            ->select();

        return json(['code'=>1,'waiter'=>$list,'service'=>$service]);
    }

    /****
     * type :1商品;2服务项目;3服务卡
     * 销量统计
     */
    public function volume(){
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            if (empty($data['type'])) {
                $data['type'] = 1;
            }
            if (!empty($data['start_time']) && !empty($data['end_time'])) {
                $time = strtotime($data['start_time']) . ',' . strtotime($data['end_time']);
            }
            if ($data['type'] == 1) {
                //查询商品销量
                $goodsWhere = [];
                if (isset($time)) {
                    $goodsWhere[] = ['b.add_time', 'between', $time]; //order b
                }
                if (!empty($data['title'])) {
                    $goodsWhere[] = ['c.bar_code|c.title', 'like', '%' . $data['title'] . '%'];   //item c
                }
                if (!empty($data['shop_id'])) {
                    $goodsWhere[] = ['b.shop_id', 'eq', $data['shop_id']];
                }
                $goods = Db::name('order_goods')
                    ->alias('a')
                    ->where($goodsWhere)
                    ->join('order b', 'a.order_id=b.id')
                    ->join('item c', 'a.item_id=c.id')
                    ->field('a.item_id,c.title as item_name,a.num,c.bar_code')
                    ->select();
            } else if ($data['type'] == 2) {
                //服务销量
                $serviceWhere = [];
                if (isset($time)) {
                    $serviceWhere[] = ['b.add_time', 'between', $time]; //order b
                }
                if (!empty($data['title'])) {
                    $serviceWhere[] = ['c.sname', 'like', '%' . $data['title'] . '%'];   //service c
                }
                if (!empty($data['shop_id'])) {
                    $serviceWhere[] = ['b.shop_id', 'eq', $data['shop_id']];
                }
                $goods = Db::name('service_goods')
                    ->alias('a')
                    ->where($serviceWhere)
                    ->join('order b', 'a.order_id=b.id')
                    ->join('service c', 'a.service_id=c.id')
                    ->field('a.service_id as item_id,c.sname as item_name,a.num')
                    ->select();
                foreach ($goods as $k => $v) {
                    $goods[$k]['bar_code'] = '';
                }
            } else {
                //服务卡销量
                $kaWhere = [];
                if (isset($time)) {
                    $kaWhere[] = ['a.add_time', 'between', $time]; //order a
                }
                if (!empty($data['title'])) {
                    $kaWhere[] = ['b.card_name', 'like', '%' . $data['title'] . '%'];   //ticket_card b
                }
                if (!empty($data['shop_id'])) {
                    $kaWhere[] = ['a.shop_id', 'eq', $data['shop_id']];
                }
                $goods = Db::name('order')
                    ->alias('a')
                    ->where($kaWhere)
                    ->join('ticket_card b', 'a.ticket_id=b.id')
                    ->field('a.ticket_id as item_id,b.card_name as item_name,a.number as num')
                    ->select();
                foreach ($goods as $k => $v) {
                    $goods[$k]['bar_code'] = '';
                }
            }
            $info = [];
            foreach ($goods as $k => $v) {
                if (isset($info[$v['item_id']])) {
                    $info[$v['item_id']][] = $v;
                } else {
                    $info[$v['item_id']][] = $v;
                }
            }
            $res = [];
            foreach ($info as $k => $val) {
                $all_num = 0;
                foreach ($val as $v) {
                    $all_num += $v['num'];
                }
                $arr = array(
                    'item_id' => $val['0']['item_id'],
                    'item_name' => $val['0']['item_name'],
                    'bar_code' => $val['0']['bar_code'],
                    'all_num' => $all_num
                );
                array_push($res, $arr);      //最终数据
            }
            $last_names = array_column($res, 'all_num');
            array_multisort($last_names, SORT_DESC, $res);
            foreach ($res as $k => $v) {
                $res[$k]['start_time'] = $data['start_time'];
                $res[$k]['end_time'] = $data['end_time'];
            }
            $total = count($res);
            $result = array("code" => 0, "count" => $total, "data" => $res);
            return json($result);
        }
        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);

        return $this->fetch();
    }

    /***
     * 订单统计
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  order(){
        $data = $this ->request->param();
        if( empty($data['type']) ){
            $data['type'] = 1;
        }
        if( $data['type'] == 1 ){
            $res = $this ->order1($data);
        }else if( $data['type'] == 2 ){
            $res = $this ->order2($data);
        }else if( $data['type'] == 3 ){
            $res = $this ->order3($data);
        }else if( $data['type'] == 4 ){
            $res = $this ->order4($data);
        }
        $this ->assign('res',$res);
        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);
        $this ->assign('data',$data);
        return $this->fetch();

    }

    /***
     * type :1全部;2商品;3服务项目;4服务卡;
     * 订单统计1:全部
     */
    public function order1($data){
        if( !empty($data['start_time']) && !empty($data['end_time']) ){
            $time = strtotime($data['start_time']).','.strtotime($data['end_time']);
        }
        $res = [];  //最终数据
        //获取销售总额
        $where = [];
        if( isset($time) ){
            $where[] = ['add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $where[] = ['shop_id','eq',$data['shop_id']];
        }
        $xiaoshou_price = Db::name('order')->where($where)->sum('amount');
        round($xiaoshou_price,2);
        array_push($res,array('price'=>$xiaoshou_price.'元','name'=>'销售总额'));
        //获取退款金额(未区分商品、服务、服务卡)
        $tWhere = [];
        if( isset($time) ){
            $tWhere[] = ['create_time','between',$time];
        }
        $tWhere[] = ['r_status','eq',1];
        if( !empty($data['shop_id']) ){
            $tWhere[] = ['shop_id','eq',$data['shop_id']];
        }
        $tuikuan_price = Db::name('order_refund')->where($tWhere)->sum('r_amount');
        round($tuikuan_price,2);
        array_push($res,array('price'=>$tuikuan_price.'元','name'=>'退款金额'));

        //获取单数
        $danshu = Db::name('order')->where($where)->count();
        array_push($res,array('price'=>$danshu.'单','name'=>'销售单数'));

        //退单数
        $tui_dan = Db::name('order_refund')->where($tWhere)->count();
        array_push($res,array('price'=>$tui_dan.'单','name'=>'退单数'));

        //余额消耗
        $yWhere[] = ['pay_way','eq',3];
        if( isset($time) ){
            $yWhere[] = ['add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $yWhere[] = ['shop_id','eq',$data['shop_id']];
        }
        $yue = $this ->getAmount($yWhere,'amount');
        array_push($res,array('price'=>$yue.'元','name'=>'余额消耗'));

        //微信收款
        if( isset($time) ){
            $wcWhere[] = ['add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $wcWhere[] = ['shop_id','eq',$data['shop_id']];
        }
        $wcWhere[] = ['pay_way','eq',1];
        $weix = $this ->getAmount($wcWhere,'amount');
        array_push($res,array('price'=>$weix.'元','name'=>'微信收款'));

        //支付宝收款
        if( isset($time) ){
            $zfWhere[] = ['add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $zfWhere[] = ['shop_id','eq',$data['shop_id']];
        }
        $zfWhere[] = ['pay_way','eq',2];
        $weix = $this ->getAmount($zfWhere,'amount');
        array_push($res,array('price'=>$weix.'元','name'=>'支付宝收款'));

        //现金收款
        if( isset($time) ){
            $xjWhere[] = ['add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $xjWhere[] = ['shop_id','eq',$data['shop_id']];
        }
        $xjWhere[] = ['pay_way','eq',5];
        $xianj = $this ->getAmount($xjWhere,'amount');
        array_push($res,array('price'=>$xianj.'元','name'=>'现金收款'));

        //银行卡收款
        if( isset($time) ){
            $yhkWhere[] = ['add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $yhkWhere[] = ['shop_id','eq',$data['shop_id']];
        }
        $yhkWhere[] = ['pay_way','eq',4];
        $yinhang = $this ->getAmount($yhkWhere,'amount');
        array_push($res,array('price'=>$yinhang.'元','name'=>'银行卡收款'));

        //美团收款
        if( isset($time) ){
            $mtWhere[] = ['add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $mtWhere[] = ['shop_id','eq',$data['shop_id']];
        }
        $mtWhere[] = ['pay_way','eq',6];
        $metuan = $this ->getAmount($mtWhere,'amount');
        array_push($res,array('price'=>$metuan.'元','name'=>'美团收款'));

//        //赠送收款
//        if( isset($time) ){
//            $zsWhere[] = ['create_time','between',$time];
//        }
//        if( !empty($data['shop_id']) ){
//            $zsWhere[] = ['shop_id','eq',$data['shop_id']];
//        }
//        $zsWhere[] = ['pay_way','eq',7];
//        $zs = $this ->getAmount($zsWhere,'old_amount');
//        array_push($res,array('price'=>$zs.'元','name'=>'赠送收款'));
        return $res;
    }

    /***
     * type :1全部;2商品;3服务项目;4服务卡;
     * 订单统计2:商品
     */
    public function order2($data){
        if( !empty($data['start_time']) && !empty($data['end_time']) ){
            $time = strtotime($data['start_time']).','.strtotime($data['end_time']);
        }
        $res = [];  //最终数据
        //销售总额
        $where = [];
        if( isset($time) ){
            $where[] = ['b.add_time','between',$time];      //order /b
        }
        if( !empty($data['shop_id']) ){
            $where[] = ['b.shop_id','eq',$data['shop_id']];
        }
        if( !empty($data['title']) ){
            $where[] = ['c.title|c.bar_code','like','%'.$data['title'].'%'];        //item c
        }
        $list = Db::name('order_goods')
            ->alias('a')
            ->where($where)
            ->join('order b','a.order_id=b.id')
            ->join('item c','a.item_id=c.id')
            ->field('a.num,a.real_price')
            ->select();
        $xiaoshou_price = $this ->getOrder2($list);
        array_push($res,array('price'=>$xiaoshou_price.'元','name'=>'销售总额'));

        //退款金额
        if( isset($time) ){
            $tWhere[] = ['b.create_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $tWhere[] = ['b.shop_id','eq',$data['shop_id']];
        }
        $tWhere[] = ['b.r_status','eq',1];
        $tWhere[] = ['a.status','eq',1];
        $tWhere[] = ['a.is_service_goods','eq',0];
        $list = Db::name('order_refund_goods')
            ->alias('a')
            ->where($tWhere)
            ->join('order_refund b','a.refund_id=b.id')
            ->field('a.r_price as real_price,a.r_num as num')
            ->select();
        $tuikuan = $this ->getOrder2($list);
        array_push($res,array('price'=>$tuikuan.'元','name'=>'退款金额'));

        //销售单数
        if( isset($time) ){
            $dWhere[] = ['add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $dWhere[] = ['shop_id','between',$data['shop_id']];
        }
        $dWhere[] = ['type','in','1,7'];
        $danshu = Db::name('order')->where($dWhere)->count();
        array_push($res,array('price'=>$danshu.'单','name'=>'销售单数'));

        //退单数
        if( isset($time) ){
            $tdWhere[] = ['b.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $tdWhere[] = ['b.shop_id','eq',$data['shop_id']];
        }
        $tdWhere[] = ['b.r_status','eq',1];
        $tdWhere[] = ['a.status','eq',1];
        $tdWhere[] = ['a.is_service_goods','eq',0];
        $list = Db::name('order_refund_goods')
            ->alias('a')
            ->where($tWhere)
            ->join('order_refund b','a.refund_id=b.id')
            ->field('a.refund_id')
            ->select();
        $tuidan1 = $this ->array_unset_tt($list,'refund_id');
        $tuidan = count($tuidan1);
        array_push($res,array('price'=>$tuidan.'单','name'=>'退单数'));

        //余额消耗
        if( isset($time) ){
            $skWhere[] = ['a.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $skWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $skWhere[] = ['a.pay_way','eq',3];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($skWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $yue = $this ->getOrder2($list);
        array_push($res,array('price'=>$yue.'元','name'=>'余额消耗'));

        //微信收款
        if( isset($time) ){
            $wxWhere[] = ['a.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $wxWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $wxWhere[] = ['a.pay_way','eq',1];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($wxWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $weix = $this ->getOrder2($list);
        array_push($res,array('price'=>$weix.'元','name'=>'微信收款'));

        //支付宝收款
        if( isset($time) ){
            $zfWhere[] = ['a.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $zfWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $zfWhere[] = ['a.pay_way','eq',2];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($zfWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $zfb = $this ->getOrder2($list);
        array_push($res,array('price'=>$zfb.'元','name'=>'支付宝收款'));

        //现金收款
        if( isset($time) ){
            $xjWhere[] = ['a.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $xjWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $xjWhere[] = ['a.pay_way','eq',5];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($xjWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $xianj = $this ->getOrder2($list);
        array_push($res,array('price'=>$xianj.'元','name'=>'现金收款'));

        //银行卡收款
        if( isset($time) ){
            $yhkWhere[] = ['a.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $yhkWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $yhkWhere[] = ['a.pay_way','eq',4];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($yhkWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $yinghangka = $this ->getOrder2($list);
        array_push($res,array('price'=>$yinghangka.'元','name'=>'银行卡收款'));

        //美团收款
        if( isset($time) ){
            $mtWhere[] = ['a.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $mtWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $mtWhere[] = ['a.pay_way','eq',6];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($mtWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $meituan = $this ->getOrder2($list);
        array_push($res,array('price'=>$meituan.'元','name'=>'美团收款'));

        //赠送收款
        if( isset($time) ){
            $zsWhere[] = ['a.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $zsWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $zsWhere[] = ['a.pay_way','eq',7];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($zsWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $zs = $this ->getOrder2($list);
        array_push($res,array('price'=>$zs.'元','name'=>'赠送收款'));
        return $res;
    }

    /***
     * 服务项目
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function order3($data){
        if( !empty($data['start_time']) && !empty($data['end_time']) ){
            $time = strtotime($data['start_time']).','.strtotime($data['end_time']);
        }
        $res = [];  //最终数据

        //销售总额
        $aWhere = [];
        if( isset($time) ){
            $aWhere[] = ['addtime','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $aWhere[] = ['sid','eq',$data['shop_id']];
        }
        if( !empty($data['title']) ){
            $aWhere[] = ['service_name','like','%'.$data['title'].'%'];
        }
        $list = Db::name('service_goods')
            ->where($aWhere)
            ->field('num,real_price')
            ->select();
        $xiaoshou_price = $this ->getOrder2($list);
        array_push($res,array('price'=>$xiaoshou_price.'元','name'=>'销售总额'));

        //退款金额
        if( isset($time) ){
            $tWhere[] = ['b.create_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $tWhere[] = ['b.shop_id','eq',$data['shop_id']];
        }
        $tWhere[] = ['b.r_status','eq',1];
        $tWhere[] = ['a.status','eq',1];
        $tWhere[] = ['a.is_service_goods','eq',1];
        if( !empty($data['title']) ){
            $tWhere[] = ['a.r_subtitle','like','%'.$data['title'].'%'];
        }
        $list = Db::name('order_refund_goods')
            ->alias('a')
            ->where($tWhere)
            ->join('order_refund b','a.refund_id=b.id')
            ->field('a.r_price as real_price,a.r_num as num')
            ->select();
        $tuikuan = $this ->getOrder2($list);
        array_push($res,array('price'=>$tuikuan.'元','name'=>'退款金额'));

        //销售单数
        if( isset($time) ){
            $dWhere[] = ['add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $dWhere[] = ['shop_id','between',$data['shop_id']];
        }
        $dWhere[] = ['type','in','2,7'];
        $danshu = Db::name('order')->where($dWhere)->count();
        array_push($res,array('price'=>$danshu.'单','name'=>'销售单数'));

        //退单数
        if( isset($time) ){
            $tdWhere[] = ['b.create_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $tdWhere[] = ['b.shop_id','eq',$data['shop_id']];
        }
        $tdWhere[] = ['b.r_status','eq',1];
        $tdWhere[] = ['a.status','eq',1];
        $tdWhere[] = ['a.is_service_goods','eq',1];
        $list = Db::name('order_refund_goods')
            ->alias('a')
            ->where($tWhere)
            ->join('order_refund b','a.refund_id=b.id')
            ->field('a.refund_id')
            ->select();
        $tuidan1 = $this ->array_unset_tt($list,'refund_id');
        $tuidan = count($tuidan1);
        array_push($res,array('price'=>$tuidan.'单','name'=>'退单数'));

        //余额消耗
        if( isset($time) ){
            $skWhere[] = ['b.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $skWhere[] = ['b.shop_id','eq',$data['shop_id']];
        }
        $skWhere[] = ['b.pay_way','in','1,2,3,4,5,6,7'];
        if( !empty($data['title']) ){
            $skWhere[] = ['a.service_name','like','%'.$data['title'].'%'];
        }
        $list = Db::name('service_goods')
            ->alias('a')
            ->where($skWhere)
            ->join('order b','a.order_id=b.id')
            ->field('a.num,a.real_price,b.pay_way')
            ->select();
        $yue = 0;   //余额消耗
        $weix = 0;  //微信收款
        $zfb = 0;  //支付宝收款
        $xianjin = 0;  //现金收款
        $yhk = 0;  //银行卡收款
        $mt = 0;  //美团收款
        $zs = 0;  //赠送收款
        foreach ($list as $v){
            if( $v['pay_way'] == 1 ){
                $weix += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 2 ){
                $zfb += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 3 ){
                $yue += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 4 ){
                $yhk += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 5 ){
                $xianjin += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 6 ){
                $mt += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 7 ){
                $zs += $v['num']*$v['real_price'];
            }
        }
        array_push($res,array('price'=>round($yue,2).'元','name'=>'余额消耗'));
        array_push($res,array('price'=>round($weix,2).'元','name'=>'微信收款'));
        array_push($res,array('price'=>round($zfb,2).'元','name'=>'支付宝收款'));
        array_push($res,array('price'=>round($xianjin,2).'元','name'=>'现金收款'));
        array_push($res,array('price'=>round($yhk,2).'元','name'=>'银行卡收款'));
        array_push($res,array('price'=>round($mt,2).'元','name'=>'美团收款'));
        array_push($res,array('price'=>round($zs,2).'元','name'=>'赠送收款'));
        return $res;
    }

    /****
     * 统计服务卡
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function order4($data){
        if( !empty($data['start_time']) && !empty($data['end_time']) ){
            $time = strtotime($data['start_time']).','.strtotime($data['end_time']);
        }
        $res = [];  //最终数据

        //销售总额
        if( isset($time) ){
            $xiaoWhere[] = ['a.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $xiaoWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $xiaoWhere[] = ['a.type','eq',5];
        if( !empty($data['title']) ){
            $xiaoWhere[] = ['b.card_name','like','%'.$data['title'].'%'];
        }
        $xiaoshou_price = Db::name('order')->alias('a')
            ->where($xiaoWhere)
            ->join('ticket_card b','a.ticket_id=b.id')
            ->sum('a.amount');
        array_push($res,array('price'=>round($xiaoshou_price,2).'元','name'=>'销售总额'));

        //退款金额
        if( isset($time) ){
            $tuiWhere[] = ['a.create_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $tuiWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $tuiWhere[] = ['a.r_status','eq',1];  //同意退货
        $tuiWhere[] = ['b.type','eq',5];
        if( !empty($data['title']) ){
            $tuiWhere[] = ['c.card_name','like','%'.$data['title'].'%'];
        }
        $dd = Db::name('order_refund')
            ->alias('a')
            ->where($tuiWhere)
            ->join('order b','a.order_id=b.id')
            ->join('ticket_card c','b.ticket_id=c.id')
            ->sum('r_amount');
        array_push($res,array('price'=>round($dd,2).'元','name'=>'退款金额'));

        //销售单数
        $xiaoshou_dan = Db::name('order')->alias('a')
            ->where($xiaoWhere)
            ->join('ticket_card b','a.ticket_id=b.id')
            ->count();
        array_push($res,array('price'=>round($xiaoshou_dan,2).'单','name'=>'销售单数'));

        //退单数
        $dd1 = Db::name('order_refund')
            ->alias('a')
            ->where($tuiWhere)
            ->join('order b','a.order_id=b.id')
            ->join('ticket_card c','b.ticket_id=c.id')
            ->count();
        array_push($res,array('price'=>round($dd1,2).'单','name'=>'退单数'));

        //获取收款金额
        if( isset($time) ){
            $skWhere[] = ['a.add_time','between',$time];
        }
        if( !empty($data['shop_id']) ){
            $skWhere[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $skWhere[] = ['a.type','eq',5];
        $skWhere[] = ['a.pay_way','in','1,2,3,4,5,6,7'];
        if( !empty($data['title']) ){
            $skWhere[] = ['b.card_name','like','%'.$data['title'].'%'];
        }
        $list = Db::name('order')->alias('a')
            ->where($skWhere)
            ->join('ticket_card b','a.ticket_id=b.id')
            ->field('a.pay_way,a.amount,a.old_amount')
            ->select();
        $yue = 0;   //余额消耗
        $weix = 0;  //微信收款
        $zfb = 0;  //支付宝收款
        $xianjin = 0;  //现金收款
        $yhk = 0;  //银行卡收款
        $mt = 0;  //美团收款
        $zs = 0;  //赠送收款
        foreach ($list as $v){
            if( $v['pay_way'] == 1 ){
                $weix += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 2 ){
                $zfb += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 3 ){
                $yue += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 4 ){
                $yhk += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 5 ){
                $xianjin += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 6 ){
                $mt += $v['num']*$v['real_price'];
            }else if( $v['pay_way'] == 7 ){
                $zs += $v['num']*$v['real_price'];
            }
        }
        array_push($res,array('price'=>round($yue,2).'元','name'=>'余额消耗'));
        array_push($res,array('price'=>round($weix,2).'元','name'=>'微信收款'));
        array_push($res,array('price'=>round($zfb,2).'元','name'=>'支付宝收款'));
        array_push($res,array('price'=>round($xianjin,2).'元','name'=>'现金收款'));
        array_push($res,array('price'=>round($yhk,2).'元','name'=>'银行卡收款'));
        array_push($res,array('price'=>round($mt,2).'元','name'=>'美团收款'));
        array_push($res,array('price'=>round($zs,2).'元','name'=>'赠送收款'));
        return $res;
    }

    /***
     * num
     * real_price
     * order2,order3
     */
    public function getOrder2($data){
        if( count($data)<=0 ){
            return 0;
        }
        $price = 0;
        foreach ($data as $v){
            $price += $v['num']*$v['real_price'];
        }
        return round($price,2);
    }

    /***
     * order1
     * @param $where :条件
     * @param $field :计算的字段
     * @return float
     */
    public function getAmount($where,$field){
        if( count($where) >0 ){
            foreach ( $where as $k=>$v ){
                $where[$k]['0'] = 'a.'.$v['0'];
            }
        }
        $list = Db::name('order') ->alias('a')->where($where)
            ->join('order_refund b','a.id=b.order_id','left')
            ->field('a.old_amount,a.amount,b.r_amount,b.r_status')
            ->select();
        $amount = 0;        //总金额
        foreach ( $list as $k=>$v ){
            if( $v['r_status'] == 1 ){
                $amountMoney = $v['amount'] - $v['r_amount'];
            }else{
                $amountMoney = $v['amount'];
            }
            $amount += $amountMoney;
        }
        return round($amount);
    }

    /***
     * 去重
     * @param $arr
     * @param $key
     * @return array
     */
    function array_unset_tt($arr,$key){
        //建立一个目标数组
        $res = array();
        foreach ($arr as $value) {
            //查看有没有重复项
            if(isset($res[$value[$key]])){
                unset($value[$key]);  //有：销毁
            }else{
                $res[$value[$key]] = $value;
            }
        }
        return $res;
    }
}