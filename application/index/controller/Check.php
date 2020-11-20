<?php
namespace app\index\controller;

use app\index\model\check\StockLogModel;
use app\index\model\check\StockLogItemModel;
use app\index\model\check\StockModel;
use app\index\model\check\StockItemModel;
use app\index\model\item\ItemModel;
use app\index\model\item\ShopItemModel;

use think\Controller;
use think\Db;
use think\Query;
use think\Request;

/**
	盘点单管理
*/
class Check extends Base
{	
	//盘点单列表
	public function index(){
		$data = $this ->request->param();
		$shop_id = $this->getUserInfo()['shop_id'];		//门店id
		$where = [];
		if( !empty($data['status']) ){
			$where[] = ['a.status','eq',$data['status']];
		}
		if( !empty($data['start_time']) ){
			$start_time = strtotime($data['start_time'].' 00:00:00');
			$where[] = ['a.create_time','>=',$start_time];
		}
		if( !empty($data['end_time']) ){
			$end_time = strtotime($data['end_time'].' 23:59:59');
			$where[] = ['a.end_time','<=',$end_time];
		}
		if( !empty($data['name']) ){
			$where[] = ['a.order_sn','like','%'.$data['name'].'%'];
		}

		$where[] = ['a.shop_id','eq',$shop_id];

		if( empty($data['page']) ){
			$data['page'] = '1,10';
		}

		$StockLog = new StockLogModel();
		$list = $StockLog
			->alias('a')
			->where($where)
			->field('a.id,a.order_sn,a.user_id,a.create_time,a.status,a.shop_id,a.end_time,a.user_id,a.is_admin')
			->order('create_time desc')
			->page($data['page'])
			->select();
		foreach ($list as $key => $val) {
			if( $val['end_time'] != 0 ){
				$list[$key]['stime'] = $val['end_time'];
			}else{
				$list[$key]['stime'] = $val['create_time'];
			}
			if( $val['is_admin'] == 1 ){
        		$val['user_id'] = Db::name('admin')->where('userid',$val['user_id'])->value('username');
        	}else{
        		$val['user_id'] = Db::name('shop_worker')->where('id',$val['user_id'])->value('name');
        	}
			unset($list[$key]['create_time']);
			unset($list[$key]['end_time']);
			unset($list[$key]['username']);
			unset($list[$key]['nickname']);
		}
		$count = $StockLog
			->alias('a')
			->where($where)
			->join('admin b','a.user_id=b.userid','LEFT')
			->count();

		return json(['code'=>200,'msg'=>'查询成功','count'=>$count,'data'=>$list]);
	}

	//添加盘点单
	public function add(){
		$shop_id = $this->getUserInfo()['shop_id'];		//门店id
		$data = $this ->request ->post();
		if( empty($data['stock_type']) ){
			return json(['code'=>-3,'msg'=>'缺少参数stock_type']);
		}
		$Item = new ItemModel();
        if( empty($data['page']) ){
            $data['page'] = '';
        }
		if( $data['stock_type'] == 1 ){	//获取库存不为0的商品
			$itemList = $Item ->getItemList($data,$shop_id);
			return json(['code'=>200,'msg'=>'查询成功','data'=>$itemList['data'],'count'=>$itemList['count']]);
		}else{
			//获取库存为0的商品
			$itemList = $Item ->getItemList1($data,$shop_id);
			return json(['code'=>200,'msg'=>'查询成功','data'=>$itemList['data'],'count'=>$itemList['count']]);
		}
	}

	//添加盘点单的操作
	public function doPost(){
		$data = $this ->request ->post();
		$order_sn = 'PD'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
		$shop_id = $this->getUserInfo()['shop_id'];
		$user_id = $this->getUserInfo()['id'];

		/*
		 * 盘点商品必须为当前门店所有商品，这里目前写的一个兼容判断由于目前没有前端技术
		 * 因此这里盘点提交的商品总数量，与可盘点总数量进行对比，如果一致则跳过验证
		 * 后期如果前端技术员来了，只需将新增页面上的动态删除取消，同时根据分页总数
		 * 进行稍加判断即可！这个的兼容处理可以根据实际情况进行删除！！！
		 * */
        $Item = (new ItemModel())->getItemList(['page'=>'1,20'],$shop_id);
		if(intval($Item['count']) != count($data['item']))
		{
            return json_encode(['code'=>500,'msg'=>'盘点商品数量与当前门店商品数量不一致,请勿删除盘点商品！','data'=>'']);
            exit;
        }

        $tWhere = [];
        $tWhere[] = ['shop_id','=',$shop_id];
        $tWhere[] = ['status','neq',3];
        $sto = Db::name('stock_log')->where($tWhere)->select();
        if( count($sto) >0 ){
            return json_encode(['code'=>500,'msg'=>'你有盘点库存未确认！','data'=>'']);
        }

		$stockLog = [];	//盘点单表数据
		if( !empty($data['remarks']) ){
			$stockLog['remarks'] = $data['remarks'];
		}

		if( count($data['item']) == 0 ){
			return json(['code'=>'-10','msg'=>'请选择盘点商品']);
		}
		$stockLog['order_sn'] = $order_sn;
		$stockLog['user_id'] = $user_id;
		$stockLog['shop_id'] = $shop_id;
		$stockLog['create_time'] = time();
		$stockLog['status'] = 1;

		$item = $data['item'];	//盘点单商品明细表数据
		$StockLog = new StockLogModel();
		$StockLogItem = new StockLogItemModel();
		// 启动事务
		Db::startTrans();
		try {
		    $stockLogId = $StockLog ->insertGetId($stockLog);
		    foreach ($item as $key => $value) {
		    	$item[$key]['log_id'] = $stockLogId;
		    }
		    $StockLogItem ->insertAll($item);
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    return json(['code'=>'500','msg'=>$e->getMessage()]);
		}
		return json(['code'=>200,'msg'=>'添加成功']);
	}

	//盘点单的编辑
	public function editRemarks(){
		$data = $this ->request->post();
		if( empty($data['id']) ){
			return json(['code'=>'-3','msg'=>'缺少单据id']);
		}

		if( count($data['item']) == 0 ){
			return json(['code'=>'-10','msg'=>'请选择盘点商品']);
		}
		if( !empty($data['remarks']) ){
			$remarks = $data['remarks'];
		}else{
			$remarks = '';
		}
		$items = $data['item'];	//盘点单商品明细表数据
		$StockLog = new StockLogModel();
		$StockLogItem = new StockLogItemModel();
		$info = Db::name('stock_log')->where('id',$data['id'])->find();
		if( $info['status'] != 1 ){
			return json(['code'=>'-20','msg'=>'已确认过的订单不能被编辑']);
		}
		$item = [];
		foreach ($items as $key => $value) {
			$arr = array(
				'log_id' =>$data['id'],
				'item_id'=>$value['item_id'],
				'item_title'=>$value['title'],
				'stock_now'=>$value['stock_now'],
				'stock_reality'=>$value['stock_reality'],
			);
			array_push($item,$arr);
		}
		// 启动事务
		Db::startTrans();
		try {
		    if( $info['remarks'] != $remarks ){
		    	$StockLog ->where('id',$data['id'])->setField('remarks',$remarks);
		    }
		    foreach ($item as $key => $value) {
		    	$item[$key]['log_id'] = $data['id'];
		    }
		    // dump($item);die;
		    $StockLogItem->where('log_id',$data['id']) ->delete();
		    $StockLogItem ->insertAll($item);
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    return json(['code'=>'500','msg'=>$e->getMessage()]);
		}
		return json(['code'=>200,'msg'=>'编辑成功']);
	}

	//确认盘点
	public function confirm(){
        /**
         * 取消店长确认盘点操作，由于无前段技术人员取消页面按钮，
         * 这里暂时直接断点提示返回，后期前端人员到岗取消按钮后可直接删除
         */
        return json(['code'=>500,'msg'=>'暂无权限，请联系管理进行确认']);exit;

		$data = $this ->request ->post();
		if( empty($data['id']) || empty($data['item']) ){
			return json(['code'=>'-3','msg'=>'id或商品信息错误']);
		}

		$item = $data['item'];
		foreach ($item as $key => $value) {
			if( $value['stock_now'] <0 ){
				return json(['code'=>'-100','msg'=>'盘点库存必须为大于0的整数']);
			}
		}

		$win = [];	//盘盈数据
		$wen = [];	//盘亏数据
		foreach ($item as $key => $value) {
			$array = array(
					'item_id'	=>$value['item_id'],
					'item'		=>$value['title'],
					'stock'		=>$value['stock_reality'],
					'num'		=>$value['stock_now'],
				);
			if( $value['stock_now'] > $value['stock_reality'] ){
				//生成盘盈单
				array_push($win, $array);
			}else if( $value['stock_now'] < $value['stock_reality'] ){
				//生成盘亏单
				array_push($wen, $array);
			}
			$item[$key]['item_title'] = $value['title'];
		    unset($item[$key]['title']);
		    unset($item[$key]['type']);
		    unset($item[$key]['type_id']);
		}
		$stockLog = [];
		if( !empty($data['remarks']) ){
			$remarks = $data['remarks'];
		}else{
			$remarks = '';
		}
		$stockLog['status'] = 2;
		$stockLog['remarks'] = $remarks;
        if( (count($win)==0) && (count($wen)==0) ){
            $stockLog['status'] = 3;
            $stockLog['end_time'] = time();
        }

		$StockLog = new StockLogModel();
		$StockLogItem = new StockLogItemModel();
		$info = Db::name('stock_log')->where('id',$data['id'])->find();
		if( $info['status'] != 1 ){
			return json(['code'=>'-20','msg'=>'已确认过该盘点单']);
		}
		// 启动事务
		Db::startTrans();
		try {
		    $StockLog ->where('id',$data['id']) ->update($stockLog);
		    foreach ($item as $key => $value) {
		    	$item[$key]['log_id'] = $data['id'];
		    }

		    $StockLogItem ->saveAll($item);	//这里面的必须要有id

		    if( count($win) >0 ){
		    	//生成盘盈单
		    	$stockData = array(
		    		'log_id'	=>$data['id'],
		    		'order_sn'	=>'PY'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8),
		    		'shop_id'	=>$info['shop_id'],
		    		'creator_id'=>$this->getUserInfo()['id'],
		    		'type'	=>1,
		    		'time'	=>time(),
		    		'status'	=>1,
		    		'remarks'	=>$remarks
		    	);
		    	$stockId = Db::name('stock')->insertGetId($stockData);
		    	foreach ($win as $key => $value) {
		    		$win[$key]['stock_id'] = $stockId;
		    	}
		    	Db::name('stock_item')->insertAll($win);
		    }

		    if( count($wen) >0 ){
		    	//生成盘亏单
		    	$stockData1 = array(
		    		'log_id'	=>$data['id'],
		    		'order_sn'	=>'PK'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8),
		    		'shop_id'	=>$info['shop_id'],
		    		'creator_id'=>$this->getUserInfo()['id'],
		    		'type'	=>2,
		    		'time'	=>time(),
		    		'status'	=>1,
		    		'remarks'	=>$remarks
		    	);
		    	$stockId1 = Db::name('stock')->insertGetId($stockData1);
		    	foreach ($wen as $key => $value) {
		    		$wen[$key]['stock_id'] = $stockId1;
		    	}
		    	Db::name('stock_item')->insertAll($wen);
		    }
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    return json(['code'=>'500','msg'=>$e->getMessage()]);
		}
		return json(['code'=>200,'msg'=>'确认盘点成功']);
	}

	//盘点单 详情
	public function info(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			return json(['code'=>-3,'msg'=>'缺少参数id']);
		}
		$StockLog = new StockLogModel();
		$stock = $StockLog ->where('id',$data['id'])->field('id,remarks')->find();
		$itemWhere = [];
		if( !empty($data['type_id']) ){
			$itemWhere[] = ['b.type_id','eq',$data['type_id']];
		}
		if( !empty($data['type']) ){
			$itemWhere[] = ['b.type','eq',$data['type_id']];
		}
		if( !empty($data['title']) ){
			$itemWhere[] = ['b.title','like','%'.$data['title'].'%'];
		}
		$itemWhere[] = ['a.log_id','eq',$data['id']];
		$StockItem = new StockLogItemModel();
		$info = $StockItem
			->alias('a')
			->where($itemWhere)
			->join('item b','a.item_id=b.id','LEFT')
			->field('a.id,a.stock_reality,a.stock_now,a.item_id,b.title,b.type_id,b.type')
			->select();
		$stock['item'] = $info;
		return json(['code'=>200,'msg'=>'查询成功','data'=>$stock]);
	}

	//盘点单删除
	public function del(){
		$data = $this ->request->param();
		if( empty($data['id']) ){
			return json(['code'=>'-3','msg'=>'缺少单据id']);
		}
		$info = Db::name('stock_log')->where('id',$data['id'])->field('id,status')->find();
		if( !$info || $info['status'] != 1 ){
			return json(['code'=>'-3','msg'=>'库存待确认或已完成订单不允许删除']);
		}
		$StockLog = new StockLogModel();
		$StockLogItem = new StockLogItemModel();
		// 启动事务
		Db::startTrans();
		try {
		    $StockLog ->where('id',$data['id'])->delete();
		    $StockLogItem ->where('log_id',$data['id'])->delete();
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    return json(['code'=>'500','msg'=>$e->getMessage()]);
		}
		return json(['code'=>200,'msg'=>'删除成功']);
	}

	//盘亏,盘盈单
	public function inventory(){
		$data = $this ->request->param();
		if( empty($data['type']) ){
			return json(['code'=>-1,'msg'=>'type参数缺失','data'=>'']);
		}
		$shop_id = $this->getUserInfo()['shop_id'];		//门店id
		$where = [];
		if( !empty($data['status']) ){
			$where[] = ['a.status','eq',$data['status']];
		}
		if( !empty($data['start_time']) ){
			$start_time = strtotime($data['start_time'].' 00:00:00');
			$where[] = ['a.time','>=',$start_time];
		}
		if( !empty($data['end_time']) ){
			$end_time = strtotime($data['end_time'].' 23:59:59');
			$where[] = ['a.end_time','<=',$end_time];
		}
		if( !empty($data['name']) ){
			$where[] = ['a.order_sn','like','%'.$data['name'].'%'];
		}

		$where[] = ['a.type','=',$data['type']];
		$where[] = ['a.shop_id','eq',$shop_id];

		if( empty($data['page']) ){
			$data['page'] = '1,10';
		}

		$StockLog = new StockModel();
		$list = $StockLog
			->alias('a')
			->where($where)
			// ->join('admin b','a.creator_id=b.userid','LEFT')
			->field('a.id,a.order_sn,a.creator_id,a.time create_time,a.status,a.shop_id,a.end_time,a.creator_id,a.is_admin')
			->page($data['page'])
			->order('create_time desc')
			->select();
		foreach ($list as $key => $val) {
			if( $val['end_time'] != 0 ){
				$list[$key]['time'] = $val['end_time'];
			}else{
				$list[$key]['time'] = $val['create_time'];
			}

			if( $val['is_admin'] == 1 ){
        		$val['creator_id'] = Db::name('admin')->where('userid',$val['creator_id'])->value('username');
        	}else{
        		$val['creator_id'] = Db::name('shop_worker')->where('id',$val['creator_id'])->value('name');
        	}
			unset($list[$key]['create_time']);
			unset($list[$key]['end_time']);
			unset($list[$key]['is_admin']);
		}
		$count = $StockLog
			->alias('a')
			->where($where)
			->join('admin b','a.creator_id=b.userid','LEFT')
			->count();

		return json(['code'=>200,'msg'=>'查询成功','count'=>$count,'data'=>$list]);
	}

	//盘亏盘盈单查看详情
	public function inventory_info(){
		$data = $this ->request->param();
		$where = [];
		if( empty($data['id']) ){
			return json(['code'=>'-3','msg'=>'缺少单据id']);
		}
		$Stock = new StockModel();
		$stock = $Stock ->where('id',$data['id'])->field('id,remarks')->find();

		$itemWhere = [];
		if( !empty($data['type_id']) ){
			$itemWhere[] = ['b.type_id','eq',$data['type_id']];
		}
		if( !empty($data['type']) ){
			$itemWhere[] = ['b.type','eq',$data['type_id']];
		}
		if( !empty($data['title']) ){
			$itemWhere[] = ['b.title','like','%'.$data['title'].'%'];
		}
		$itemWhere[] = ['a.stock_id','eq',$data['id']];
		$StockItem = new StockItemModel();
		$info = $StockItem
			->alias('a')
			->where($itemWhere)
			->join('item b','a.item_id=b.id','LEFT')
			->field('a.id,a.stock,a.num,a.remarks,b.title,b.type_id,b.type')
			->select();
		$stock['item'] = $info;
		return json(['code'=>200,'msg'=>'查询成功','data'=>$stock]);
	}

	//库存查询
	public function stock_about(){
		$data = $this ->request ->post();
		$data['shop_id'] = $this->getUserInfo()['shop_id'];		//门店id
		if( empty($data['page']) ){
			$page = '';
		}else{
			$page = $data['page'];
		}
		$Item = new ItemModel();
		$goods = $Item ->getgood($data)->page($page)->select()->append(['type_ids','types']);
		$total = $Item ->getgood($data)->count();
		return json(['code'=>'200','msg'=>'查询成功','count'=>$total,'data'=>$goods]);
	}

	/***
     * 第一期做盘点单，全部查询商品
     */
	public function getItems(){
        $data = $this ->request ->param();
        if( empty($data['page']) ){
            $data['page'] = '';
        }
        $shop_id = $this->getUserInfo()['shop_id'];		//门店id
        $Item = new ItemModel();
        $item = $Item ->twoItems($data,$shop_id);
        return json(['code'=>200,'msg'=>'a获取成功','count'=>$item['count'],'data'=>$item['data']]);
    }
}