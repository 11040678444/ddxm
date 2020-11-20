<?php
namespace app\wxshop\controller;

use app\index\model\Order\OrderGoodsModel;
use think\Controller;
use think\Db;
use think\Query;
use think\Request;
// use app\index\controller\Base;
use app\wxshop\model\order\OrderModel;
use app\wxshop\model\order\OrderRefundModel;
use app\wxshop\model\statistics\StatisticsLogModel;
use app\wxshop\model\shareholder\ShareholderModel;
/**
	股东数据
*/
class Shareholder extends Controller
{	
	protected function initialize()
    {
    	header('Content-Type:application/json; charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Max-Age:86400'); // 允许访问的有效期
        header('Access-Control-Allow-Headers:*');
        header('Access-Control-Allow-Methods:OPTIONS, GET, POST, DELETE');
    }

	//营业收款
	//allArray所有的总金额，allList所有的总数据
	public function index(){
		$data = $this ->request ->param();
		if ( empty($data['page']) ) {
			$data['page'] = '1,5';
		}
		$Statistics = new StatisticsLogModel();

		$where = [];
		if ( !empty($data['start_time']) && !empty($data['end_time']) ) {
			$timeWhere = strtotime(($data['start_time'].'00:00:00')).','.strtotime(($data['end_time'].'23:59:59'));
		}else{
			return json(['code'=>'-3','msg'=>'请传入起止时间','data'=>'']);
		}
		$shop_id = $data['shop_id']?$data['shop_id']:18;

		$allArray = [];

		$allWhere = [];
		$allWhere[] = array('create_time','between',$timeWhere);
		$allWhere[] = array('shop_id','=',$shop_id);
		//营业收款->余额充值
		$allWhere[] = array('type','=',1);

		$yueCz_price = $Statistics->getAllPricedata($allWhere);	//余额充值总数
		$yueczData = array(
				'all_price'		=>$yueCz_price
			);

		//营业收款->消费收款
		array_splice($allWhere,2);
		$allWhere[] = array('type','=',3);
		$xiaohao_price = $Statistics->getAllPricedata($allWhere);	//消耗收款总数
		$xiaohaoData = array(
				'all_price'		=>$xiaohao_price
			);

		//营业收款总数据
		$Business_express = array(
//				'all_price'		=>$yueCz_price+$gouka_price+$xiaohao_price,
				'all_price'		=>$yueCz_price+$xiaohao_price,
				'data'			=>array(
						'yueczData'		=>$yueczData,
						'xiaohaoData'	=>$xiaohaoData
					)
			);
		$allArray['Business_express'] = $Business_express;		//营业收款

		//营业收入->余额消耗
		array_splice($allWhere,2);
		$allWhere[] = array('type','=',4);

		$yueXh_price = $Statistics->getAllPricedata($allWhere);		
		$yueXhData = array(
				'all_price'		=>$yueXh_price
			);

		//营业收入->消费消耗
		array_splice($allWhere,2);
		$allWhere[] = array('type','=',5);

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

		$Business_receipts = array(
//				'all_price'		=>$yueXh_price+$item_waibao_price+$tuina_waibao_price+$xiaofeiXh_price,
				'all_price'		=>$yueXh_price+$item_waibao_price+$xiaofeiXh_price,
				'data'			=>array(
						'yueXhData'	=>$yueXhData,
						'xiaofeiXhData'	=>$xiaofeiXhData,
						'itemWaibaoData'=>$itemWaibaoData,
//						'tuinaWaibaoData'=>$tuinaWaibaoData
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

        //营业成本
		$Operating_cost = array(
//				'all_price'		=>$item_cost_price+$yingye_price+$item_waibaocost_price+$tuina_waibao_price,
				'all_price'		=>$item_cost_price+$yingye_price+$item_waibaocost_price,
				'data'			=>array(
						'itemCostData'		=>$itemCostData,
						'yingyeData'		=>$yingyeData,
						'itemWaibaocostData'=>$itemWaibaocostData,
//                        'tuinaWaibaoData'=>$tuinaWaibaoData
					)
			);
		$allArray['Operating_cost'] = $Operating_cost;		//营业成本


        /***
         * 计算推拿外包分润
         */
        $map = [];  //推拿外包分润的条件
        $map[] = ['shop_id','eq',$data['shop_id']];
        $map[] = ['type','eq',7];
        if (
            (strtotime($data['end_time'].'23:59:59') > strtotime('2020-03-31 23:59:59'))
            && (strtotime($data['start_time']) < strtotime('2020-03-31 23:59:59'))
        )
        {
            //筛选时间包含两个阶段，则分成两个部分查询，分别放在 “收入”、“成本”里面
            $map1 = $map;   //第一个小于4/1的推拿外包分润，“营业收入”
            $map1[] = ['create_time','between',strtotime($data['start_time']).','.strtotime('2020-03-31 23:59:59')];
            $tuina_waibao_price1 = $Statistics->getAllPricedata($map1);
            $allArray['Business_receipts']['data']['tuinaWaibaoPrice'] = ['all_price'=>$tuina_waibao_price1];
            $allArray['Business_receipts']['all_price'] += $tuina_waibao_price1;
            $map2 = $map;   //第二个大于4/1的推拿外包分润，“营业成本”
            $map2[] = ['create_time','between',strtotime('2020-04-01 00:00:00').','.strtotime($data['end_time'].' 23:59:59')];
            $tuina_waibao_price2 = $Statistics->getAllPricedata($map2);
            $allArray['Operating_cost']['data']['tuinaWaibaoPrice'] = ['all_price'=>$tuina_waibao_price2];
            $allArray['Operating_cost']['all_price'] += $tuina_waibao_price2;
        }else{
            $map[] = ['create_time','between',strtotime($data['start_time']).','.strtotime($data['end_time'].'23:59:59')];
            $tuina_waibao_price3 = $Statistics->getAllPricedata($map);
            if ( strtotime($data['start_time']) > strtotime('2020-03-31 23:59:59') )
            {
                //开始时间在2020/4/1日之后则计算在营业成本里面
                $allArray['Operating_cost']['data']['tuinaWaibaoPrice'] = ['all_price'=>$tuina_waibao_price3];
                $allArray['Operating_cost']['all_price'] += $tuina_waibao_price3;
            }else{
                //结束时间在2020/4/1日之前则计算在营业收入里面
                $allArray['Business_receipts']['data']['tuinaWaibaoPrice'] = ['all_price'=>$tuina_waibao_price3];
                $allArray['Business_receipts']['all_price'] += $tuina_waibao_price3;
            }
        }
		//营业利润
		$Operating_profit = ($allArray['Business_receipts']['all_price']*100/100) - ($allArray['Operating_cost']['all_price']*100/100);
		$allArray['Operating_profit'] = array('all_price'=>$Operating_profit);	//营业利润

		//查询数据
		$listWhere = [];
		$listWhere[] = array('create_time','between',$timeWhere);
		$listWhere[] = array('shop_id','=',$shop_id);
		//type:1余额充值,2购卡,3小号收款,4余额消耗,5消费消耗,6商品外包分润, 7推拿外包分润,8商品成本,9营业费用,10外包商品成本
		if ( empty($data['type_first']) && empty($data['type_two']) ) {
			$types = '1,2,3';
			$listWhere[] = ['type','in','1,2,3'];
		}
		if( empty($data['type_first']) && !empty($data['type_two']) ){
			$listWhere[] = ['type','=',$data['type_two']];
		}
		if( !empty($data['type_first']) && !empty($data['type_two']) ){
			$listWhere[] = ['type','=',$data['type_two']];
		}
		if( !empty($data['type_first']) && empty($data['type_two']) ){
			if( $data['type_first'] == 1 ){
				$listWhere[] = ['type','in','1,2,3'];
			}
			if( $data['type_first'] == 2 ){
				$listWhere[] = ['type','in','4,5,6,7'];
			}
			if( $data['type_first'] == 3 ){
				$listWhere[] = ['type','in','8,9,10'];
			}
		}
		$dataList = $Statistics->getAllList($listWhere,$data['page']);		//数据
        foreach ($dataList as $k=>$v){
            if( $v['type'] =="商品成本" && $v['pay_way'] =="门店自用" ){
                $dataList[$k]['title'] = "自用商品";
            }
        }
		if( $data['type_two'] == 6 || $data['type_two'] == 7 ){
			foreach ($dataList as $key => $value) {
				$dataList[$key]['order_sn'] = '';
//				$dataList[$key]['pay_way'] = '';
			}
		}else if($data['type_two'] == 8 || $data['type_two'] == 10 ){
			foreach ($dataList as $key => $value) {
//				$dataList[$key]['pay_way'] = '';
			}
		}else if($data['type_two'] == 9){
			foreach ($dataList as $key => $value) {
//				$dataList[$key]['pay_way'] = '';
				$dataList[$key]['order_sn'] = '';
			}
		}

		return json(['code'=>200,'msg'=>'请求成功','data'=>array('price'=>$allArray,'data'=>$dataList)]);
	}

	//判断是否为股东
	public function isShareholder(){
		$data = $this ->request ->param();
		if( empty($data['mobile']) ){
			return json(['code'=>'-3','msg'=>'手机号不能为空','data'=>'']);		
		}
		$Shareholder = new ShareholderModel();
		$where['mobile'] = $data['mobile'];
		$where['status'] = 1;
		$info = $Shareholder ->where($where)->field('id,mobile,shop_ids')->find();
		if( $info ){
			return json(['code'=>200,'msg'=>'此用户为股东','data'=>$info]);
		}else{
			return json(['code'=>'-6','msg'=>'此用户不是股东','data'=>'']);
		}
	}

	/***
     * 订单详情
     */
    //订单商品明细
    public function order_goods_list(){
        $data = $this ->request ->param();
        if ( empty($data['order_id']) ) {
            return json(['code'=>'-3','msg'=>'请传入订单id','data'=>'']);
        }
        $item = [];
        $OrderGoods = new OrderGoodsModel();
        $order_type = Db::name('order')->where('id',$data['order_id'])->value('type');
        if( !$order_type ){
            return json(['code'=>'-3','msg'=>'订单出现错误','data'=>'']);
        }
        if( $order_type == 1 ){
            $item1 = $OrderGoods
                ->alias('og')
                ->join('order o','og.order_id=o.id')
                ->where('order_id',$data['order_id'])
                ->field('og.*,o.add_time')
                ->select();
            foreach ($item1 as $key => $value) {
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                    'id'    =>$value['item_id'],
                    'title' =>$value['subtitle'],
                    'num' =>$value['num'],
                    'md_cost_price' =>$value['all_oprice'],    //门店总成本
                    'gs_cost_price' =>$value['gs_cost_price'],    //公司总成本
                    'price' =>$value['real_price'], //单价
                    'all_price' =>bcmul($value['real_price'],$value['num'],2),   //总售价
                    'status'    =>$value['status'],
                    'is_service_goods'  =>0,
                    'refund'    =>$refund,
                    'add_time'  =>$value['add_time']
                );
                array_push($item,$arr);
            }
        }
        if( $order_type == 2 ){
            $item2 = Db::name('service_goods')->alias('og')
                ->join('order o','og.order_id=o.id')
                ->where('order_id',$data['order_id'])
                ->field('og.*,o.add_time')
                ->select();
            foreach ($item2 as $key => $value) {
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                    'id'    =>$value['service_id'],
                    'title' =>$value['service_name'],
                    'num' =>$value['num'],
                    'md_cost_price' =>0,    //门店总成本
                    'gs_cost_price' =>0,    //公司总成本
                    'price' =>$value['real_price'],
                    'all_price' =>bcmul($value['real_price'],$value['num'],2),   //总售价
                    'status'    =>$value['status'],
                    'is_service_goods'  =>1,
                    'refund'    =>$refund,
                    'add_time'  =>$value['add_time']
                );
                array_push($item,$arr);
            }
        }
        if( $order_type == 7 ){
            $item1 = $OrderGoods
                ->alias('og')
                ->join('order o','og.order_id=o.id')
                ->where('order_id',$data['order_id'])
                ->field('og.*,o.add_time')
                ->select();
            $item2 = Db::name('service_goods')->alias('og')
                ->join('order o','og.order_id=o.id')
                ->where('order_id',$data['order_id'])
                ->field('og.*,o.add_time')
                ->select();
            foreach ($item1 as $key => $value) {
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                    'id'    =>$value['item_id'],
                    'title' =>$value['subtitle'],
                    'num' =>$value['num'],
                    'md_cost_price' =>$value['all_oprice'],    //门店总成本
                    'gs_cost_price' =>$value['gs_cost_price'],    //公司总成本
                    'price' =>$value['real_price'],
                    'all_price' =>bcmul($value['real_price'],$value['num'],2),   //总售价
                    'status'    =>$value['status'],
                    'is_service_goods'  =>0,
                    'refund'    =>$refund,
                    'add_time'  =>$value['add_time']
                );
                array_push($item,$arr);
            }

            foreach ($item2 as $key => $value) {
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                    'id'    =>$value['service_id'],
                    'title' =>$value['service_name'],
                    'num' =>$value['num'],
                    'md_cost_price' =>0,    //门店总成本
                    'gs_cost_price' =>0,    //公司总成本
                    'price' =>$value['real_price'],
                    'all_price' =>bcmul($value['real_price'],$value['num'],2),   //总售价
                    'status'    =>$value['status'],
                    'is_service_goods'  =>1,
                    'refund'    =>$refund,
                    'add_time'  =>$value['add_time']
                );
                array_push($item,$arr);
            }
        }

        foreach ($item as $k=>$v){
            if( $v['refund'] >0 ){
                $item[$k]['title'] = $v['title']."(已退".$v['refund']."件)";
            }
        }
        foreach ( $item as $k=>$v ){
            $item[$k]['price'] = bcmul($v['price'],$v['num'],2);
            $item[$k]['cost_price'] = $v['md_cost_price'];  //默认展示总成本是门店总成本
        }

        //如果是选择的是公司总部门店、则展示公司成本
        //如果是在修改版本之前,有公司总部门店的订单，则就不需要改变展示的成本
        //两个if是
        if ( isset($data['shop_id']) && $data['shop_id'] == 1 )
        {
            //如果当前选择的门店是公司总部、则改变展示的成本价格
            foreach ( $item as $k=>$v )
            {
                if ( $v['add_time'] >= 1592814655 )
                {
                    //这个时间戳是当时改版时的时间戳，上线是需改成正式上线时间戳
                    $item[$k]['cost_price'] = $v['gs_cost_price'];  //默认展示总成本是门店总成本
                }

            }
        }
        $count = count($item);
        return json(['code'=>$count==0?'100':'200','msg'=>'查询成功','count'=>$count,'data'=>$item]);
    }
}