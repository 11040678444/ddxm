<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use app\admin\model\warehousing\WarehousingModel;
use app\admin\model\warehousing\WarehousingItemModel;
use app\admin\controller\Deposit;
use app\admin\model\allot\AllotModel;

/**
	直接入库controller
*/
class Warehousing extends Adminbase
{
	//直接入库列表
	public function index(){
		if ($this->request->isAjax()) {
			$Warehousing = new WarehousingModel(); 
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $data = $this ->request->param();
            $where = [];
            if( !empty($data['name']) ){
            	$where[] = ['sn','like','%'.$data['name'].'%'];
            }
            if( !empty($data['status']) ){
            	$where[] = ['status','=',$data['status']];
            }
            if( !empty($data['supplier_id']) ){
            	$where[] = ['supplier_id','=',$data['supplier_id']];
            }
            if( !empty($data['shop_id']) ){
            	$where[] = ['shop_id','=',$data['shop_id']];
            }
            if( !empty($data['start_time']) ){
            	$start_time = strtotime($data['start_time']);
            	$where[] = ['create_time','>=',$start_time];
            }
            if( !empty($data['end_time']) ){
            	$end_time = strtotime($data['end_time']);
            	$where[] = ['create_time','<=',$end_time];
            }
            $list = $Warehousing->where($where)->page($page,$limit)
            	->order('create_time desc')
            	->select()->append(['message','item_list','barcode_list','price_list','num_list','allprice_list']);

            $total =  $Warehousing->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		$shop = Db::name('shop')->where('status',1)->field('id,name')->select();
		$this ->assign('shop',$shop);

		$sup = Db::name('supplier')->where('del',0)->field('id,supplier_name')->select();
		$this ->assign('sup',$sup);
		return $this->fetch();
	}

	//添加
	public function add(){
		$res = $this->request->post();
		$data['shop'] = db::name("shop")->where('status','1')->field("id,name")->select();
        $data['supplier'] = db::name("supplier")->where('del',0)->field("id,supplier_name")->select();
        $data['item'] = Db::name("item_category")->where("pid",0)->where("status",1)->where('type',1)->field("id,cname")->select();
        $this->assign("data",$data);
		return $this->fetch();	
	}

	//直接入库操作
	public function doPost(){
		$data = $this ->request ->post()['data'];

		//判断当前门店是否存在对应商品的盘盈/盘亏单
        $res = (new AllotModel)->isStockException($data['shop_id'],$data['item_id']);

		$item_name = $data['item_name'];
		$item_id = $data['item_id'];
		$bar_code = $data['bar_code'];
		$p_type = $data['p_type'];
		$level_id = $data['level_id'];
		$cname = $data['cname'];
		$levels_id = $data['levels_id'];
		$number = $data['number'];
		$price = $data['price'];
		$money = $data['money'];

		$itemData = [];	//入库商品明细数据
		$shopItemData = [];	//商品库存需要添加的数据
		$purPriceData = [];	//商品成本表数据明细
		foreach ($item_id as $key => $value) {
			$arr = array(
				'price'	=>$price[$key],
				'num'	=>$number[$key],
				'all_price'	=>$money[$key],
				'item_id'	=>$item_id[$key],
				'item_name'	=>$item_name[$key],
				'bar_code'	=>$bar_code[$key],
				'type_id'	=>$level_id[$key],
				'type_name'	=>$p_type[$key],
				'type'	=>$levels_id[$key],
				'type_names'=>$cname[$key],
			);
			array_push($itemData, $arr);

			$sarr = array(
				'shop_id'	=>$data['shop_id'],
				'item_id'	=>$item_id[$key],
				'stock'		=>$number[$key]	//需要增加的库存
			);
			array_push($shopItemData, $sarr);

			$parr = array(
				'shop_id'	=>$data['shop_id'],
				'type'		=>1,
				'item_id'	=>$item_id[$key],
				'md_price'	=>$price[$key],
				'store_cose'=>$price[$key],
				'stock'		=>$number[$key],
				'time'		=>time(),
			);
			array_push($purPriceData, $parr);
		}
		//直接入库单数据
		$warehousing =array(
			'shop_id'	=>$data['shop_id'],
			'sn'	=>'RK'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8),
			'supplier_id'	=>$data['supplier'],
			'user_id'	=>session('admin_user_auth')['uid'],
			'status'	=>1,
			'store_time'	=>0,
			'amount'	=>$data['amount'],
			'create_time'	=>time(),
			'remark'	=>$data['remarks'],
			'quser_id'	=>0
		);
		$Warehousing = new WarehousingModel();
		$WarehousingItem = new WarehousingItemModel();
		// 启动事务
		Db::startTrans();
		try {
		    $WarehousingId = $Warehousing ->insertGetId($warehousing);	//直接入库
		    foreach ($itemData as $key => $value) {
		    	$itemData[$key]['warehousing_id'] = $WarehousingId;
		    }
		    $WarehousingItem ->insertAll($itemData);	//直接入库商品

		    foreach ($shopItemData as $key => $value) {	//添加商品库存
		    	$where = [];
		    	$where['shop_id'] = $value['shop_id'];
		    	$where['item_id'] = $value['item_id'];
		    	$info = [];
		    	$info = Db::name('shop_item')->where($where)->find();
		    	if( $info ){
		    		Db::name('shop_item')->where($where)->setInc('stock',$value['stock']);
		    	}else{
		    		$where['stock'] = $value['stock'];
		    		Db::name('shop_item')->insert($where);
		    	}
		    }
		    foreach ($purPriceData as $key => $value) {
		    	$purPriceData[$key]['pd_id'] = $WarehousingId;
		    }
		    Db::name('purchase_price')->insertAll($purPriceData);	//添加成本


		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    return json(['code'=>0,'msg'=>$e->getMessage()]);
		}
		return json(['code'=>1,'msg'=>'入库成功']);
	}

	//取消入库
	public function quxiao(){
		$data = $this ->request->param();
		if( empty($data['id']) ){
			$this ->error('单据id参数错误');
		}
		$Warehousing = new WarehousingModel();
		$info = $Warehousing ->where('id',$data['id'])->field('id,status,shop_id,supplier_id')->find();
		if( $info['status'] == 0 ){
			$this ->error('此订单已取消');
		}

		$WarehousingItem = new WarehousingItemModel();
		$item = $WarehousingItem ->getItem($data['id']);

        //判断当前门店是否存在对应商品的盘盈/盘亏单
        $res = (new AllotModel)->isStockException($info['shop_id'],array_column($item->toArray(),'item_id'));

		foreach ($item as $key => $value) {
			$where = [];
			$where['shop_id'] = $info['shop_id'];
			$where['item_id'] = $value['item_id'];
			$list = [];
			$list = Db::name('shop_item')->where($where)->field('stock')->find();
			if( !$list || $list['stock']<$value['num'] ){
				$this ->error('取消失败,【'.$value['item_name']."】库存不足,此订单不允许取消");
			}
		}
		//取消入库操作
		// 启动事务
		Db::startTrans();
		try {
		    $Warehousing ->where('id',$data['id'])->update(['quser_id'=>session('admin_user_auth')['uid'],'status'=>2,'store_time'=>time()]);	//更改状态

		    // 修改商品库存表
		    foreach($item as $key=>$value){
		    	$where = [];
				$where['shop_id'] = $info['shop_id'];
				$where['item_id'] = $value['item_id'];
				Db::name('shop_item')->where($where)->setDec('stock',$value['num']);
		    }

		    //减少成本消耗
		    foreach ($item as $key => $value) {
		    	$purWhere = [];
		    	$purWhere[] = ['item_id','=',$value['item_id']];
		    	$purWhere[] = ['shop_id','=',$info['shop_id']];
		    	$purWhere[] = ['stock','>',0];

		    	$Deposit = new Deposit;
		    	$tt = $Deposit->getCostPrice($purWhere,$value['num']);
				foreach ($tt as $k => $v) {
					Db::name('purchase_price') ->where('id',$v['id'])->setDec('stock',$v['num']);
				}
		    }
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    $this ->error($e->getMessage());
		}
		$this ->success('取消成功');
	}

	//获取分类信息
	public function getCategory(){
		$pid = $this ->request ->param('id');
		if( empty($pid) ){
			$pid = 0;
		}
		$where['pid'] = $pid;
        $where['status'] = 1;
        $where['type'] = 1;
        $list = Db::name('item_category')->where($where)->order('sort asc')->field('id,cname')->select();
        $count = Db::name('item_category')->where($where)->order('sort asc')->field('id,cname')->count();
        return json(['count'=>$count,'data'=>$list,'msg'=>'查询成功','result'=>true]);
	}
}