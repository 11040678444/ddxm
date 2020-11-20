<?php
namespace app\index\controller;


use app\wxshop\model\statistics\StatisticsLogModel;
use think\Controller;
use think\Db;
use think\Query;
use think\Request;

class Statistics extends Base
{
    /***
     * 会员统计
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function member(){
		$data = $this ->request ->param();
		$shop_id = $this->getUserInfo()['shop_id'];
        $shop_code = Db::name('shop')->where('id',$shop_id)->value('code');
		$start_time = $this ->request->param('start_time');
		$end_time = $this ->request->param('end_time');
		if( !$end_time || !$start_time ){
			return json(['code'=>-3,'msg'=>'请选择起止时间']);
		}
		$time = strtotime($start_time." 00:00:00").','.strtotime($end_time." 23:59:59");
		//总会员，与时间无关
        $allWhere[] = ['shop_code','eq',$shop_code];
		$allWhere[] = ['status','eq',1];
		$all_member_total = Db::name('member')->where($allWhere)->column('id');     //此门店总的会员ID,下面统计总的分销员使用
        $all_total = count($all_member_total);//总会员人数

		//消费会员总
        $oWhere[] = ['shop_id','eq',$shop_id];
		$oWhere[] = ['add_time','between',$time];
		$oWhere[] = ['member_id','neq',0];
		$xiao_total = Db::name('order')->where($oWhere)->field('member_id')->select();	//消费总订单
        $xiao_total = $this ->array_unset_tt($xiao_total,'member_id');  //去重
        $xiao_total = count($xiao_total);       //消费总会员

        //新客会员
        $oWhere[] = ['a.shop_id','eq',$shop_id];
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
        $snWhere[] = ['shop_id','eq',$shop_id];
        $snWhere[] = ['add_time','between',$time];
        $snWhere[] = ['member_id','eq',0];
        $sanCount = Db::name('order')->where($snWhere)->count();	//散客总人数

        //总分销员
        $resWhere = [];
        $resWhere[] = ['member_id','in',implode(',',$all_member_total)];
//        $resWhere[] = ['create_time','between',$time];
        $all_distributor = Db::name('retail_user')->where($resWhere)->count();  //总分销人数
        //有效总粉丝
        $resWhere = [];
        $resWhere[] = ['fans_id','in',implode(',',$all_member_total)];
//        $resWhere[] = ['create_time','between',$time];
        $resWhere[] = ['status','eq',1];
        $all_youxiao_fans = Db::name('retail_fans')->where($resWhere)->count(); //总粉丝

        //无效总粉丝
        $resWhere = [];
        $resWhere[] = ['fans_id','in',implode(',',$all_member_total)];
        $resWhere[] = ['status','eq',0];
        $all_wuxiao_fans = Db::name('retail_fans')->where($resWhere)->count(); //总粉丝

        //总粉丝
        $resWhere = [];
        $resWhere[] = ['fans_id','in',implode(',',$all_member_total)];
        $all_fans = Db::name('retail_fans')->where($resWhere)->count(); //总粉丝

        //新增分销员
        $resWhere = [];
        $resWhere[] = ['member_id','in',implode(',',$all_member_total)];
        $resWhere[] = ['create_time','between',$time];
        $new_add_distributor = Db::name('retail_user')->where($resWhere)->count();  //总分销人数
        //新增粉丝
        $resWhere = [];
        $resWhere[] = ['fans_id','in',implode(',',$all_member_total)];
        $resWhere[] = ['create_time','between',$time];
//        $resWhere[] = ['status','eq',1];
        $new_add_fans = Db::name('retail_fans')->where($resWhere)->count(); //总粉丝
		$result = [
		    'all_total'=>$all_total,
            'new_member'=>$new_member,
            'xiao_total'=>$xiao_total,
            'sanCount'=>$sanCount,
            'all_distributor'=>$all_distributor,
            'all_fans'=>$all_fans,
//            'all_youxiao_fans'=>$all_youxiao_fans,
//            'all_wuxiao_fans'=>$all_wuxiao_fans,
            'new_add_distributor'=>$new_add_distributor,
            'new_add_fans'=>$new_add_fans
        ];
		return json(['code'=>200,'msg'=>'查询成功','data'=>$result]);
	}

    /***
     * 充值统计
     * all_price 充值总额
     * price 退款金额
     * @return \think\response\Json
     */
	public function cz(){
        $start_time = $this ->request->param('start_time');
        $end_time = $this ->request->param('end_time');
        $shop_id = $this->getUserInfo()['shop_id'];
        if( !$end_time || !$start_time ){
            return json(['code'=>-3,'msg'=>'请选择起止时间']);
        }
        $time = strtotime($start_time." 00:00:00").','.strtotime($end_time." 23:59:59");
        $where[] = ['shop_id','eq',$shop_id];
        $where[] = ['create_time','between',$time];
        $all_price = Db::name('member_recharge_log')->where($where)->sum('price');  //总充值

        $where[] = ['price','<',0];
        $price = Db::name('member_recharge_log')->where($where)->sum('price');  //退款金额
        $res = [];
        $res = ['all_price'=>$all_price,'price'=>$price];
        return json(['code'=>200,'msg'=>'查询成功','data'=>$res]);
    }

    /***
     * 支出统计
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function expenditure(){
	    $data = $this ->request ->param();
        $shop_id = $this ->getUserInfo()['shop_id'];

        if( !empty($data['type_id']) ){
            $typeWhere[] = ['id','eq',$data['type_id']];
        }
        $typeWhere[] = ['delete_time','<=',0];
        $list = Db::name('expenditure_types')->where($typeWhere)->field('title,id as type_id')->order('sort asc')->select();   //所以类型

        foreach ($list as $k=>$v){
            $list[$k]['price'] = 0;
        }

        if( !empty($data['start_time']) && !empty($data['end_time']) ){
            $time = strtotime($data['start_time']." 00:00:00").','.strtotime($data['end_time']." 23:59:59");
            $dataWhere[] = ['create_time','between',$time];
        }
        if( !empty($data['type_id']) ){
            $dataWhere[] = ['type_id','eq',$data['type_id']];
        }

        $dataWhere[] = ['shop_id','eq',$shop_id];
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
        return json(['code'=>200,'msg'=>'获取成功','all_price'=>$all_price,'data'=>$list]);
    }


    /***
     * 服务统计
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function service(){
        $data = $this ->request->param();
        if( !empty($data['start_time']) && !empty($data['end_time']) ){
            $time = strtotime($data['start_time']." 00:00:00").','.strtotime($data['end_time']." 23:59:59");
            $where[] = ['b.add_time','between',$time];
        }
        //waiter_id  service_id
        $shop_id = $this ->getUserInfo()['shop_id'];
        $where[] = ['a.sid','eq',$shop_id];
        $where[] = ['b.is_online','eq',0];
        if( !empty($data['service_id']) ){
            $where[] = ['a.service_id','eq',$data['service_id']];
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
        $consumptionWhere[] = ['shop_id','eq',$shop_id];
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
            $res[$k]['start_time'] = $data['start_time'].' 00:00:00';
            $res[$k]['end_time'] = $data['end_time'].' 23:59:59';
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
        return json(['code'=>200,'msg'=>'获取成功','data'=>$info]);
    }

    /****
     * type :1商品;2服务项目;3服务卡
     * 销量统计
     */
    public function volume(){
        $data = $this->request->param();
        $shop_id = $this ->getUserInfo()['shop_id'];
        if( empty($data['type']) ){
            return json(['code'=>'-2','msg'=>'缺少参数type']);
        }
        if( !empty($data['start_time']) && !empty($data['end_time']) ){
            $time = strtotime($data['start_time']." 00:00:00").','.strtotime($data['end_time']." 23:59:59");
        }else{
            return json(['code'=>100,'msg'=>'请传入时间']);
        }
        if( $data['type'] == 1 ){
            //查询商品销量
            if( isset($time) ){
                $goodsWhere[] = ['b.add_time','between',$time]; //order b
            }
            if( !empty($data['title']) ){
                $goodsWhere[] = ['c.bar_code|c.title','like','%'.$data['title'].'%'];   //item c
            }
            $goodsWhere[] = ['b.shop_id','eq',$shop_id];
            $goods = Db::name('order_goods')
                ->alias('a')
                ->where($goodsWhere)
                ->join('order b','a.order_id=b.id')
                ->join('item c','a.item_id=c.id')
                ->field('a.item_id,c.title as item_name,a.num,c.bar_code')
                ->select();
        }else if( $data['type'] == 2 ){
            //服务销量
            if( isset($time) ){
                $serviceWhere[] = ['b.add_time','between',$time]; //order b
            }
            if( !empty($data['title']) ){
                $serviceWhere[] = ['c.sname','like','%'.$data['title'].'%'];   //service c
            }
            $serviceWhere[] = ['b.shop_id','eq',$shop_id];
            $goods = Db::name('service_goods')
                    ->alias('a')
                    ->where($serviceWhere)
                    ->join('order b','a.order_id=b.id')
                    ->join('service c','a.service_id=c.id')
                    ->field('a.service_id as item_id,c.sname as item_name,a.num')
                    ->select();
            foreach ($goods as $k=>$v){
                $goods[$k]['bar_code'] = '';
            }
        }else{
            //服务卡销量
            if( isset($time) ){
                $kaWhere[] = ['a.add_time','between',$time]; //order a
            }
            if( !empty($data['title']) ){
                $kaWhere[] = ['b.card_name','like','%'.$data['title'].'%'];   //ticket_card b
            }
            $kaWhere[] = ['a.shop_id','eq',$shop_id];
            $goods = Db::name('order')
                ->alias('a')
                ->where($kaWhere)
                ->join('ticket_card b','a.ticket_id=b.id')
                ->field('a.ticket_id as item_id,b.card_name as item_name,a.number as num')
                ->select();
            foreach ($goods as $k=>$v){
                $goods[$k]['bar_code'] = '';
            }

        }
        $info = [];
        foreach ($goods as $k=>$v){
            if( isset($info[$v['item_id']]) ){
                $info[$v['item_id']][] = $v;
            }else{
                $info[$v['item_id']][] = $v;
            }
        }
        $res = [];
        foreach ($info as $k=>$val){
            $all_num = 0;
            foreach ($val as $v){
                $all_num += $v['num'];
            }
            $arr = array(
                'item_id'   =>$val['0']['item_id'],
                'item_name'   =>$val['0']['item_name'],
                'bar_code'   =>$val['0']['bar_code'],
                'all_num'   =>$all_num
            );
            array_push($res,$arr);      //最终数据
        }
//        dump($res);die;
        $last_names = array_column($res,'all_num');
        array_multisort($last_names,SORT_DESC,$res);
        foreach ($res as $k=>$v){
            $res[$k]['start_time'] = $data['start_time'].' 00:00:00';
            $res[$k]['end_time'] = $data['end_time'].' 23:59:59';
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$res]);
    }

    /****
     * 订单统计版本1.1
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function order1_1(){
        $data = $this ->request->param();
        if( $data['type'] == 1 ){
            $res = $this ->order1($data);
        }else if( $data['type'] == 2 ){
            $res = $this ->order2($data);
        }else if( $data['type'] == 3 ){
            $res = $this ->order3($data);
        }else if( $data['type'] == 4 ){
            $res = $this ->order4($data);
        }
        return json($res);
    }

    /****
     * 订单统计版本1.2
     * 目前使用的版本
     */
    public function order(){
        $data = $this ->request->param();
        $order1 = self::order1($data);
        if( $order1['code'] != 200 ){
            return json($order1);
        }
        $where = [];
        if ( !empty($data['start_time']) && !empty($data['end_time']) ) {
            $timeWhere = strtotime(($data['start_time'].'00:00:00')).','.strtotime(($data['end_time'].'23:59:59'));
        }else{
            return json(['code'=>'-3','msg'=>'请传入起止时间','data'=>'']);
        }
        $shop_id = $this ->getUserInfo()['shop_id'];
        $sale = self::sale($shop_id,$timeWhere);
        $res = [];
        $arr = [
            'price' =>  $sale['Business_express']['all_price'].'元',
            'name'  =>'门店业绩'
        ];
        array_push($res,$arr);
        foreach ($order1['data'] as $k=>$v){
            if( $k == 5 || $k == 6 || $k == 7 || $k == 8 || $k == 9 || $k == 10 || $k==11){
                array_push($res,$v);
            }
        }
        //暂时删除赠送
        unset($res['6']);
        array_push($res,array('price'=>$sale['Business_receipts']['all_price'].'元','name'=>'营业收入'));
        array_push($res,array('price'=>$sale['Business_receipts']['data']['yueXhData']['all_price'].'元','name'=>'余额消耗'));
        array_push($res,array('price'=>$sale['Business_receipts']['data']['xiaofeiXhData']['all_price'].'元','name'=>'消费消耗'));
        array_push($res,array('price'=>$sale['Business_receipts']['data']['itemWaibaoData']['all_price'].'元','name'=>'商品外包'));
        array_push($res,array('price'=>$sale['Business_receipts']['data']['tuinaWaibaoData']['all_price'].'元','name'=>'推拿外包'));
        return json(['code'=>200,'msg'=>'获取成功','data'=>$res]);
    }

    /***
     * 查询营业数据
     */
    public function sale($shop_id,$timeWhere){
        $Statistics = new StatisticsLogModel();
        $allArray = [];

        $allWhere = [];
        $allWhere[] = array('create_time','between',$timeWhere);
        $allWhere[] = array('shop_id','=',$shop_id);
        $allWhere[] = array('pay_way','<>',3);//剔除充值订单退单退余额的数据（退到余额任然属于收益）
        //营业收款->余额充值
        $allWhere[] = array('type','=',1);

        $yueCz_price = $Statistics->getAllPricedata($allWhere);	//余额充值总数
        $yueczData = array(
            'all_price'		=>$yueCz_price
        );

        //营业收款->购卡
        array_splice($allWhere,2);
        array_splice($allWhere,3);
        array_push($allWhere, array('type','=',2));

        $gouka_price = $Statistics->getAllPricedata($allWhere);	//购卡总数
        $goukaData = array(
            'all_price'		=>$gouka_price
        );

        //营业收款->消费收款
        array_splice($allWhere,2);
        $allWhere[] = array('type','=',3);
//        dump($allWhere);die;
        $xiaohao_price = $Statistics->getAllPricedata($allWhere);	//消耗收款总数
        $xiaohaoData = array(
            'all_price'		=>$xiaohao_price
        );

        //营业收入总数据
        $Business_express = array(
            'all_price'		=>$yueCz_price+$gouka_price+$xiaohao_price,
            'data'			=>array(
                'yueczData'		=>$yueczData,
                'goukaData'		=>$goukaData,
                'xiaohaoData'	=>$xiaohaoData
            )
        );
        $allArray['Business_express'] = $Business_express;		//营业收款

        //营业收入->余额消耗
        array_splice($allWhere,2);
        $allWhere[] = array('type','=',4);
//		$allWhere[] = array('pay_way','eq',3);

        $yueXh_price = $Statistics->getAllPricedata($allWhere);
        $yueXhData = array(
            'all_price'		=>$yueXh_price
        );

        //营业收入->消费消耗
        array_splice($allWhere,2);
        $allWhere[] = array('type','=',5);
//		$allWhere[] = array('pay_way','neq',3);

        $xiaofeiXh_price = $Statistics->getAllPricedata($allWhere);
        $xiaofeiXhData = array(
            'all_price'		=>$xiaofeiXh_price
        );

        //营业收入->商品外包分润
        array_splice($allWhere,2);
        $allWhere[] = array('type','=',6);

        $item_waibao_price = $Statistics->getAllPricedata($allWhere);
        $itemWaibaoData = array(
            'all_price'		=>$item_waibao_price
        );

        //营业收入->推拿外包分润
        array_splice($allWhere,2);
        $allWhere[] = array('type','=',7);

        $tuina_waibao_price = $Statistics->getAllPricedata($allWhere);
        $tuinaWaibaoData = array(
            'all_price'		=>$tuina_waibao_price
        );

        $Business_receipts = array(
            'all_price'		=>$yueXh_price+$item_waibao_price+$tuina_waibao_price+$xiaofeiXh_price,
            'data'			=>array(
                'yueXhData'	=>$yueXhData,
                'xiaofeiXhData'	=>$xiaofeiXhData,
                'itemWaibaoData'=>$itemWaibaoData,
                'tuinaWaibaoData'=>$tuinaWaibaoData
            )
        );
        $allArray['Business_receipts'] = $Business_receipts;		//营业收入

        //营业成本->商品成本
        array_splice($allWhere,2);
        $allWhere[] = array('type','=',8);

        $item_cost_price = $Statistics->getAllPricedata($allWhere);
        $itemCostData = array(
            'all_price'		=>$item_cost_price
        );

        //营业成本->营业费用
        array_splice($allWhere,2);
        $allWhere[] = array('type','=',9);

        $yingye_price = $Statistics->getAllPricedata($allWhere);
        $yingyeData = array(
            'all_price'		=>$yingye_price
        );

        //营业成本->外包商品成本
        array_splice($allWhere,2);
        $allWhere[] = array('type','=',10);

        $item_waibaocost_price = $Statistics->getAllPricedata($allWhere);
        $itemWaibaocostData = array(
            'all_price'		=>$item_waibaocost_price
        );

        $Operating_cost = array(
            'all_price'		=>$item_cost_price+$yingye_price+$item_waibaocost_price,
            'data'			=>array(
                'itemCostData'		=>$itemCostData,
                'yingyeData'		=>$yingyeData,
                'itemWaibaocostData'=>$itemWaibaocostData
            )
        );
        $allArray['Operating_cost'] = $Operating_cost;		//营业成本
        //营业利润
        $Operating_profit = ($Business_receipts['all_price']*100/100) - ($Operating_cost['all_price']*100/100);
        $allArray['Operating_profit'] = array('all_price'=>$Operating_profit);	//营业利润
        return $allArray;
    }


    /***
     * type :1全部;2商品;3服务项目;4服务卡;
     * 订单统计1:全部
     */
    public function order1($data){
        if( !empty($data['start_time']) && !empty($data['end_time']) ){
            $time = strtotime($data['start_time']." 00:00:00").','.strtotime($data['end_time']." 23:59:59");
        }else{
            return ['code'=>'100','msg'=>'请选中起止时间','data'=>''];
        }
        if( empty($data['type']) || $data['type'] != 1 ){
            return ['code'=>'100','msg'=>'接口请求错误','data'=>''];
        }
        $shop_id = $this ->getUserInfo()['shop_id'];
        $res = [];  //最终数据

        //获取销售总额
        $where[] = ['add_time','between',$time];
        $where[] = ['shop_id','eq',$shop_id];
        $xiaoshou_price = Db::name('order')->where($where)->sum('amount');
        round($xiaoshou_price,2);
        array_push($res,array('price'=>$xiaoshou_price.'元','name'=>'销售总额'));

        //获取退款金额(未区分商品、服务、服务卡)
        $tWhere[] = ['create_time','between',$time];
        $tWhere[] = ['r_status','eq',1];
        $tWhere[] = ['shop_id','eq',$shop_id];
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
        $where[] = ['pay_way','eq',3];
        $yue = $this ->getAmount($where,'amount');
        array_push($res,array('price'=>$yue.'元','name'=>'余额消耗'));

        //微信收款
        array_splice($where,2);
        $where[] = ['pay_way','eq',1];
        $weix = $this ->getAmount($where,'amount');
        array_push($res,array('price'=>$weix.'元','name'=>'微信收款'));

        //支付宝收款
        array_splice($where,2);
        $where[] = ['pay_way','eq',2];
        $weix = $this ->getAmount($where,'amount');
        array_push($res,array('price'=>$weix.'元','name'=>'支付宝收款'));

        //现金收款
        array_splice($where,2);
        $where[] = ['pay_way','eq',5];
        $xianj = $this ->getAmount($where,'amount');
        array_push($res,array('price'=>$xianj.'元','name'=>'现金收款'));

        //银行卡收款
        array_splice($where,2);
        $where[] = ['pay_way','eq',4];
        $yinhang = $this ->getAmount($where,'amount');
        array_push($res,array('price'=>$yinhang.'元','name'=>'银行卡收款'));
//        dump($yinhang);die;
        //美团收款
        array_splice($where,2);
        $where[] = ['pay_way','eq',6];
        $metuan = $this ->getAmount($where,'amount');
        array_push($res,array('price'=>$metuan.'元','name'=>'美团收款'));

        //赠送收款
        array_splice($where,2);
        $where[] = ['pay_way','eq',7];
        $zs = $this ->getAmount($where,'old_amount');
        array_push($res,array('price'=>$zs.'元','name'=>'赠送收款'));

        //超级汇买
        array_splice($where,2);
        $where[] = ['pay_way','eq',12];
        $zs = $this ->getAmount($where,'old_amount');
        array_push($res,array('price'=>$zs.'元','name'=>'超级汇买'));
        return ['code'=>200,'msg'=>'获取成功','data'=>$res];
    }

    /***
     * type :1全部;2商品;3服务项目;4服务卡;
     * 订单统计2:商品
     */
    public function order2($data){
        if( !empty($data['start_time']) && !empty($data['end_time']) ){
            $time = strtotime($data['start_time']." 00:00:00").','.strtotime($data['end_time']." 23:59:59");
        }else{
            return ['code'=>100,'msg'=>'请选中起止时间','data'=>''];
        }
        if( empty($data['type']) || $data['type'] != 2 ){
            return ['code'=>100,'msg'=>'接口请求错误','data'=>''];
        }
        $shop_id = $this ->getUserInfo()['shop_id'];
        $res = [];  //最终数据

        //销售总额
        $where[] = ['b.add_time','between',$time];      //order /b
        $where[] = ['b.shop_id','eq',$shop_id];
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
        $tWhere[] = ['b.create_time','between',$time];
        $tWhere[] = ['b.shop_id','eq',$shop_id];
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
        $dWhere[] = ['add_time','between',$time];
        $dWhere[] = ['shop_id','between',$shop_id];
        $dWhere[] = ['type','in','1,7'];
        $danshu = Db::name('order')->where($dWhere)->count();
        array_push($res,array('price'=>$danshu.'单','name'=>'销售单数'));

        //退单数
        $tdWhere[] = ['b.create_time','between',$time];
        $tdWhere[] = ['b.shop_id','eq',$shop_id];
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
        $skWhere[] = ['a.add_time','between',$time];
        $skWhere[] = ['a.shop_id','eq',$shop_id];
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
        array_splice($skWhere,2);
        $skWhere[] = ['a.pay_way','eq',1];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($skWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $weix = $this ->getOrder2($list);
        array_push($res,array('price'=>$weix.'元','name'=>'微信收款'));

        //支付宝收款
        array_splice($skWhere,2);
        $skWhere[] = ['a.pay_way','eq',2];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($skWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $zfb = $this ->getOrder2($list);
        array_push($res,array('price'=>$zfb.'元','name'=>'支付宝收款'));

        //现金收款
        array_splice($skWhere,2);
        $skWhere[] = ['a.pay_way','eq',5];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($skWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $xianj = $this ->getOrder2($list);
        array_push($res,array('price'=>$xianj.'元','name'=>'现金收款'));

        //银行卡收款
        array_splice($skWhere,2);
        $skWhere[] = ['a.pay_way','eq',4];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($skWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $yinghangka = $this ->getOrder2($list);
        array_push($res,array('price'=>$yinghangka.'元','name'=>'银行卡收款'));

        //美团收款
        array_splice($skWhere,2);
        $skWhere[] = ['a.pay_way','eq',6];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($skWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $meituan = $this ->getOrder2($list);
        array_push($res,array('price'=>$meituan.'元','name'=>'美团收款'));

        //赠送收款
        array_splice($skWhere,2);
        $skWhere[] = ['a.pay_way','eq',7];
        $list = Db::name('order_goods')
            ->alias('b')
            ->where($skWhere)
            ->join('order a','b.order_id=a.id')
            ->field('b.num,b.real_price')
            ->select();
        $zs = $this ->getOrder2($list);
        array_push($res,array('price'=>$zs.'元','name'=>'赠送收款'));

        return ['code'=>200,'msg'=>'获取成功','data'=>$res];
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
            $time = strtotime($data['start_time']." 00:00:00").','.strtotime($data['end_time']." 23:59:59");
        }else{
            return ['code'=>100,'msg'=>'请选中起止时间','data'=>''];
        }
        if( empty($data['type']) || $data['type'] != 3 ){
            return ['code'=>100,'msg'=>'接口请求错误','data'=>''];
        }
        $shop_id = $this ->getUserInfo()['shop_id'];
        $res = [];  //最终数据

        //销售总额
        $aWhere[] = ['addtime','between',$time];
        $aWhere[] = ['sid','eq',$shop_id];
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
        $tWhere[] = ['b.create_time','between',$time];
        $tWhere[] = ['b.shop_id','eq',$shop_id];
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
        $dWhere[] = ['add_time','between',$time];
        $dWhere[] = ['shop_id','between',$shop_id];
        $dWhere[] = ['type','in','2,7'];
        $danshu = Db::name('order')->where($dWhere)->count();
        array_push($res,array('price'=>$danshu.'单','name'=>'销售单数'));

        //退单数
        $tdWhere[] = ['b.create_time','between',$time];
        $tdWhere[] = ['b.shop_id','eq',$shop_id];
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
        $skWhere[] = ['b.add_time','between',$time];
        $skWhere[] = ['b.shop_id','eq',$shop_id];
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
        return ['code'=>200,'msg'=>'获取成功','data'=>$res];
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
            $time = strtotime($data['start_time']." 00:00:00").','.strtotime($data['end_time']." 23:59:59");
        }else{
            return ['code'=>100,'msg'=>'请选中起止时间','data'=>''];
        }
        if( empty($data['type']) || $data['type'] != 4 ){
            return ['code'=>100,'msg'=>'接口请求错误','data'=>''];
        }
        $shop_id = $this ->getUserInfo()['shop_id'];
        $res = [];  //最终数据

        //销售总额
        $xiaoWhere[] = ['a.add_time','between',$time];
        $xiaoWhere[] = ['a.shop_id','eq',$shop_id];
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
        $tuiWhere[] = ['a.create_time','between',$time];
        $tuiWhere[] = ['a.shop_id','eq',$shop_id];
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
        $skWhere[] = ['a.add_time','between',$time];
        $skWhere[] = ['a.shop_id','eq',$shop_id];
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
        return ['code'=>200,'msg'=>'获取成功','data'=>$res];
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
            ->field('a.old_amount,a.amount,b.r_amount,b.r_status,a.pay_way,a.id')
            ->select();

        $amount = 0;        //总金额
        foreach ( $list as $k=>$v ){
            if ( $v['id'] == 30000 )    //此订单的数据是手动制作，有问题，单独处理
            {
                $amountMoney = -1299;
            }else{
                if( $v['r_status'] == 1 ){
                    $amountMoney = $v['amount'] - $v['r_amount'];
                }else{
                    $amountMoney = $v['amount'];
                }
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