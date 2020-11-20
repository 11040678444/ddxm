<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\shop\ShopModel;
use app\admin\model\deposit\SupplierModel;
use app\admin\model\deposit\StockModel;
use app\admin\model\deposit\StockItemModel;
use app\admin\model\deposit\StockLogItemModel;
use app\admin\model\deposit\StockLogModel;
use app\admin\model\item\ItemModel;
use app\admin\model\deposit\PurchasePrice;
use libs\ExportTool;
use think\Db;

/**
	进销存管理  库存管理与盘点管理
*/
class Deposit extends Adminbase
{
	//仓库列表
	public function shop_list(){
		if ($this->request->isAjax()) {
			$Shop = new ShopModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            // $name = $this ->request->param('name');
            $where = [];
            $where[] = ['status','=',1];
            // if( !empty($name) ){
            // 	$where[] = ['name','like',"%$name%"];
            // }
            $where[] = ['code','eq',0];
            $list = $Shop->where($where)->page($page,$limit)->select();
            $total =  $Shop->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		return $this->fetch();
	}

	//添加仓库
	public function shop_add(){
		$data = $this ->request ->param();
		if( !empty($data['id']) ){
			$Shop = new ShopModel();
			$list = $Shop ->where('id',$data['id'])->field('id,name')->find();
			$this ->assign('list',$list);
		}
		return $this->fetch();
	}

	//仓库添加编辑逻辑
	public function shop_doPost(){
		$Shop = new ShopModel();
		$data = $this ->request ->post();
		if( empty($data['name']) ){
			$this ->error('请输入仓库名称');
		}

		if( empty($data['id']) ){
			$data['addtime'] = time();
			$data['update_time'] = time();
			$result = $Shop ->insert($data);
		}else{
			$data['update_time'] = time();
			$result = $Shop ->where('id',$data['id'])->update($data);
		}
		if( $result ){
			$this ->success('操作成功',url("Deposit/shop_list"));
		}else{
			$this ->error('操作失败');
		}
	}

	//仓库删除
	public function shop_del(){
		$data = $this ->request->param();
		if( empty($data['id']) ){
			$this ->error('参数为空');
		}
		$Shop = new ShopModel();
		$result = $Shop->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
		if( $result ){
			$this ->success('操作成功',url("Deposit/shop_list"));
		}else{
			$this ->error('操作失败');
		}
	}

	//供应商列表
	public function supplier_list(){
		if ($this->request->isAjax()) {
			$Supplier = new SupplierModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $name = $this ->request->param('name');
            $where = [];
            $where[] = ['del','=',0];
            if( !empty($name) ){
            	$where[] = ['mobile|supplier_name','like',"%$name%"];
            }
            $list = $Supplier->where($where)->page($page,$limit)->select();
            $total =  $Supplier->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		return $this->fetch();
	}

	//供应商添加
	public function supplier_add(){
		$data = $this ->request ->param();
		if( !empty($data['id']) ){
			$Supplier = new SupplierModel();
			$list = $Supplier ->where('id',$data['id'])->find();
			$this ->assign('list',$list);
		}
		return $this->fetch();
	}

	//供应商添加操作
	public function supplier_doPost(){
		$data = $this ->request ->post();
		if( empty($data['supplier_name']) ){
			return json(['code'=>0,'msg'=>'请输入供应商名称']);
		}
		if( empty($data['contacts']) ){
			return json(['code'=>0,'msg'=>'请输入联系人']);
		}
		if( empty($data['mobile']) ){
			return json(['code'=>0,'msg'=>'请输入联系人电话']);
		}
		$rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule, $data['mobile']);
        if(!$ruleResult){
        	return json(['code'=>0,'msg'=>'手机号格式不正确']);
        }
		$Supplier = new SupplierModel();
		$info = $Supplier ->where('mobile',$data['mobile'])->find();

		if( empty($data['id']) ){
			if( $info ){
				return json(['code'=>0,'msg'=>'该手机号已存在']);
			}
			$data['creater'] = session('admin_user_auth')['username'];
			$data['creater_id'] = session('admin_user_auth')['uid'];
			$data['del'] = 0;
			$data['update_id']	= session('admin_user_auth')['uid'];
			$data['update_time']  = time();
			$Supplier ->insert($data);
		}else{
			if( $info && $info['id'] != $data['id'] ){
				return json(['code'=>0,'msg'=>'该手机号已存在']);
			}
			$data['update_id']	= session('admin_user_auth')['uid'];
			$data['update_time']  = time();
			$Supplier ->where('id',$data['id'])->update($data);
		}
		return json(['code'=>1,'msg'=>'操作成功']);
	}

	//供应商删除
	public function supplier_del(){
		$data = $this ->request->param();
		if( empty($data['id']) ){
			$this ->error('参数为空');
		}
		$Supplier = new SupplierModel();
		$result = $Supplier->where('id',$data['id'])->update(['del'=>1,'del_time'=>time(),'del_staff'=>session('admin_user_auth')['uid']]);
		if( $result ){
			$this ->success('操作成功',url("Deposit/supplier_list"));
		}else{
			$this ->error('操作失败');
		}
	}

	//库存查询
	public function stock_list(){
		if ($this->request->isAjax()) {
			$Item = new ItemModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
           	$data = $this ->request ->param();

			$list = $Item ->getgood($data)->page($page,$limit)->order('time desc')->select();	//每个仓库的商品列表

			// $list = $Item ->getItemStock($list); // 获取商品的库存
			if( $list != 0 ){
				$list = $Item ->costPrice($list); //获取合计成本

			}
			if( $list != 0 ){
				foreach ($list as $key => $value) {
					$list[$key]['shop_id'] = Db::name('shop')->where('id',$value['shop_id'])->value('name');
				}
			}

			//计算总成本和
            if( !empty($data['shop_id']) || !empty($data['type_id']) || !empty($data['type']) || !empty($data['name']) || !empty($data['stock']) ){
//                $all_list = $Item ->getgood($data)->select();dump($all_list);die;
//                $all_list = $Item ->costPrice($all_list); //获取合计成本
//                $allCost = 0;       //总成本
//                if( $all_list != 0 ){
//                    foreach ( $all_list as $k=>$v ){
//                        $allCost += $v['cost_price'];
//                    }
//                }
                $where = [];
                if( !empty($data['shop_id']) ){
                    $where[] = ['b.shop_id','eq',$data['shop_id']];
                }
                if( !empty($data['type_id']) ){
                    $where[] = ['a.type_id','eq',$data['type_id']];
                }
                if( !empty($data['type']) ){
                    $where[] = ['a.type','eq',$data['type']];
                }
                if( !empty($data['name']) ){
                    $where[] = ['a.title|a.bar_code','like','%'.$data['name'].'%'];
                }
                if( !empty($data['stock']) ){
                    if( $data['stock'] == 1 ){
                        $where[] = ['b.stock','eq',0];
                    }else{
                        $where[] = ['b.stock','neq',0];
                    }
                }
                $all_list = db('item')
                        ->alias('a')
                        ->field('a.id,a.title,a.type_id,a.type,a.bar_code,b.shop_id,b.stock,b.shop_id as shop_ids')
                        ->join('shop_item b','a.id=b.item_id')
                        ->where($where)
                        ->select();
                $allCost = array_sum(array_column(empty($Item ->costPrice($all_list))?[]:$Item ->costPrice($all_list),'cost_price'));
                $total = count($all_list);
            }else{
                $allCost = 0;       //总成本
            }

            $allCost = sprintf("%.2f",substr(sprintf("%.3f", $allCost), 0, -2));
            $total =  isset($total) ? $total : $Item->getgood($data)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list ,"allCost" => $allCost);
            return json($result);
		}
		$shop = Db::name('shop')->where('status',1)->field('id,name')->select();
        $this ->assign('shop',$shop);
        $where['pid'] = 0;
        $where['status'] = 1;
        $where['type'] = 1;
        $category = Db::name('item_category')->where($where)->order('sort asc')->select();
        $this ->assign('category',$category);
		return $this->fetch();
	}

	//盘点单列表
	public function pandian_list(){
		if ($this->request->isAjax()) {
			$StockLog = new StockLogModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $data = $this ->request->param();
            $where = [];
            if( !empty($data['name']) ){
            	$name = $data['name'];
            	$where[] = ['order_sn','like',"%$name%"];
            }
            if( !empty($data['shop_id']) ){
            	$where[] = ['shop_id','=',$data['shop_id']];
            }
            if( !empty($data['status']) ){
            	$where[] = ['status','=',$data['status']];
            }
            if( !empty($data['time']) ){
            	$time = strtotime($data['time']);
            	$end_time = strtotime($data['end_time']);
            	$where[] = ['create_time','between',$time.','.$end_time];
            }
            
            $list = $StockLog
            	->where($where)
            	->page($page,$limit)
            	->order('create_time desc')
            	->select();
            foreach ($list as $key => $value) {
            	if( $value['is_admin'] ){
            		$value['user_id'] = Db::name('admin')->where('userid',$value['user_id'])->value('username');
            	}else{
            		$value['user_id'] = Db::name('shop_worker')->where('id',$value['user_id'])->value('name');
            	}
            	if( $value['end_time'] != 0 ){
					$list[$key]['time'] = $value['end_time'];
				}else{
					$list[$key]['time'] = $value['create_time'];
				}
            }
            $total =  $StockLog->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		$Shop = new ShopModel();
		$shop = $Shop ->where(['status'=>1])->field('id,name')->select();
		$this ->assign('shop',$shop);
		return $this->fetch();
	}

	//新增盘点单
	public function pandian_add(){

		$data = $this ->request ->param();
		if( !empty($data['id']) ){
			$StockLog = new StockLogModel();
			$list = Db::name('stock_log')->where('id',$data['id'])->find();
			$this ->assign('list',$list);

			$StockItem = new StockLogItemModel();
			$itemGoods = $StockItem ->getItemList($data['id'],$data)->select();
			foreach ($itemGoods as $key => $value) {
				if( $value['stock_now'] > $value['stock_reality'] ){
					$itemGoods[$key]['da'] = 2;	//大
				}else if( $value['stock_now'] < $value['stock_reality'] ){
					$itemGoods[$key]['da'] = 1;	//小
				}else{
					$itemGoods[$key]['da'] = 0;	//小
				}	
			}
	        $this ->assign('itemDatas',$itemGoods);
		}

		if( $data['shop_id'] !== 0 && !empty($data['shop_id']) ){
			//查询此仓库是否还有未完成的盘赢盘亏
			$st = $this ->isOk($data['shop_id']);
			$st = json_decode($st,true);
			if( $st['code'] == 100 ){
				//判断是否存在未确认的库存
				$data['shop_id'] = 0;
				$this ->error('此门店存在未确认库存的盘盈/盘亏单,请先确认库存');die;
			}
			$where = [];
			if( !empty($data['name']) ){
				$name = $data['name'];
				$where[] = ['a.title','like',"%$name%"];
			}
			if( $data['parent'] != 0 ){
				$where[] = ['a.type_id','=',$data['parent']];
			}
			if( $data['type'] != 0 ){
				$where[] = ['a.child','=',$data['child']];
			}
			if( $data['shop_id'] != 0 ){
				$where[] = ['b.shop_id','=',$data['shop_id']];
			}
			$where[] = ['b.stock','>',0];
			$Item = new ItemModel();
			$goods = $Item
					->alias('a')
					->join('shop_item b','a.id=b.item_id')
					->where($where)
					->field('a.id,a.title,a.type_id,a.type,b.shop_id,b.stock')
					->select();
			//查询库存
			// $goods = $Item ->getItemStock1($goods,1);
			$this ->assign('goods',$goods);
		}
		//仓库
		$Shop = new ShopModel();
		$shop = $Shop ->where(['status'=>1])->field('id,name')->select();
		$this ->assign('shop',$shop);

		$data['item'] = Db::name("item_category")->where("pid",0)->where("status",1)->where('type',1)->field("id,cname")->select();
        $this->assign("data",$data);
		//1级分类
		$typeId = Db::name('item_category')->where(['pid'=>0,'status'=>1,'type'=>1])->field('id,cname')->select();
		$this ->assign('typeId',$typeId);
		return $this->fetch();
	}

	//判断是否存在未完成的盘盈盘亏单
	public function isOk($shop_id){
		$tWhere = [];
		$tWhere[] = ['shop_id','=',$shop_id];
		$tWhere[] = ['status','neq',3];
		$sto = Db::name('stock_log')->where($tWhere)->select();
		if( count($sto) >0 ){
			return json_encode(['code'=>100,'msg'=>'存在未确认库存单','data'=>$sto]);
		}else{	
			return json_encode(['code'=>200,'msg'=>'已全部确认','data'=>'']);
		}
	}

	public function isOk1(){
		$shop_id = $this ->request ->param('shop_id');
		$ss = $this ->isOk($shop_id);
		$st = json_decode($ss,true);
		return json(['code'=>$st['code'],'msg'=>$st['msg']]);
	}

	//新增盘点时搜索商品
	public function search_item(){
		$data = $this ->request ->param();
		$where = [];
		if( !empty($data['title']) ){
			$where[] = ['a.title','like','%'.$data['title'].'%'];
		}
		if( $data['type_id'] != 0 ){
			$where[] = ['a.type_id','=',$data['type_id']];
		}
		if( $data['type'] != 0 ){
			$where[] = ['a.type','=',$data['type_id']];
		}
		if( $data['shop_id'] != 0 ){
			$where[] = ['b.shop_id','=',$data['shop_id']];
		}
		$where[] = ['b.stock','eq',0];
		$page = '';
		$Item = new ItemModel();
		$goods = $Item
				->alias('a')
				->join('shop_item b','a.id=b.item_id')
				->where($where)
				->page($page)
				->field('a.id,a.title,a.type_id,a.type,b.shop_id,b.stock')
				->select();
		$goods = $Item ->getItemStock1($goods,0);	//搜索库存为0的商品
		$total =  $Item
				->alias('a')
				->join('shop_item b','a.id=b.item_id')
				->where($where)
				->count();
		return json(["code" => 0, "count" => $total, "data" => $goods]);
	}

	//盘点操作
	public function pandian_doPost(){
		$data = $this ->request ->param();
		$itemData = json_decode($data['data'],true);
		$itemDatas = [];		//盘点单
		foreach ($itemData as $key => $value) {
			if( $value['item_id'] == '' || $value['stock'] == '' || $value['num'] == '' ){
				return json(['code'=>0,'msg'=>'请输入必填内容']);
			}
			$arr = array(
				'item_id'	=>$value['item_id'],
				'item_title'	=>$value['title'],
				'stock_reality'	=>$value['stock'],
				'stock_now'	=>$value['num'],
			);
			array_push($itemDatas, $arr);
		}
		$StockLog = new StockLogModel();
		$StockLogItem = new StockLogItemModel();
		if( empty($data['id']) ){
			// 添加
			// 生成盘点单数据
			$log = array(
					'order_sn'	=>'PD'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8),
					'shop_id'	=>$data['shop_id'],
					'user_id'	=>session('admin_user_auth')['uid'],
					'create_time'=>time(),
					'status'	=>1,
					'remarks'	=>$data['remarks'],
					'is_admin'	=>1
				);
			
			// 启动事务
			Db::startTrans();
			try {
			    $stockLogId = $StockLog ->insertGetId($log);
			    foreach ($itemDatas as $key => $value) {
			    	$itemDatas[$key]['log_id'] = $stockLogId;
			    }
			    $StockLogItem ->insertAll($itemDatas);
			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			    return json(['code'=>'500','msg'=>$e->getMessage()]);
			}
		}else{
			//编辑
			$log = array(
				'shop_id'	=>$data['shop_id'],
				'remarks'	=>$data['remarks'],
			);
			// 启动事务
			Db::startTrans();
			try {
			    $stockLogId = $StockLog ->where('id',$data['id'])->update($log);
			    foreach ($itemDatas as $key => $value) {
			    	$itemDatas[$key]['log_id'] = $data['id'];
			    }
			    $StockLogItem ->where('log_id',$data['id'])->delete();
			    $StockLogItem ->insertAll($itemDatas);
			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			    return json(['code'=>'500','msg'=>$e->getMessage()]);
			}

		}
		return json(['code'=>1,'msg'=>'操作成功']);
	}

	//确认盘点单
	public function stock_sure(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('id参数错误');
		}
		$StockLog = new StockLogModel();
		$list = Db::name('stock_log')->where('id',$data['id'])->find();
		$this ->assign('list',$list);

		$StockItem = new StockLogItemModel();
		$itemGoods = $StockItem ->getItemList($data['id'],$data)->select();
		foreach ($itemGoods as $key => $value) {
			if( $value['stock_now'] > $value['stock_reality'] ){
				$itemGoods[$key]['da'] = 2;	//大
			}else if( $value['stock_now'] < $value['stock_reality'] ){
				$itemGoods[$key]['da'] = 1;	//小
			}else{
				$itemGoods[$key]['da'] = 0;	//小
			}	
		}
//        dump($itemGoods);die;
        $this ->assign('itemDatas',$itemGoods);

        //仓库
		$Shop = new ShopModel();
		$shop = $Shop ->where(['status'=>1])->field('id,name')->select();
		$this ->assign('shop',$shop);

		$data['item'] = Db::name("item_category")->where("pid",0)->where("status",1)->where('type',1)->field("id,cname")->select();
        $this->assign("data",$data);
		//1级分类
		$typeId = Db::name('item_category')->where(['pid'=>0,'status'=>1,'type'=>1])->field('id,cname')->select();
		$this ->assign('typeId',$typeId);
        return $this->fetch();
	}

    /**
     * 盘点单详细导出
     */
	public function export()
    {
        try
        {
                $id = input('id/d');

                empty($id) ? return_error('参数错误') : '';

                $StockItem = new StockLogItemModel();
                $itemGoods = $StockItem->getItemList($id,['field'=>'item_title,bar_code,stock_reality,stock_now'])->select()->toArray();

                //表头
                $heard = ['商品名称','条形码','当前库存','盘点库存'];

                $res = (new ExportTool())->ExportData('盘点单',$heard,$itemGoods);

        }catch (\Exception $e){
            returnJson(500,$e->getCode(),$e->getMessage());
        }
    }

	//盘点单确认操作
	public function suer_doPost()
	{
		$data = $this ->request ->post();
		$item = json_decode($data['data'],true);
		$win = [];	//盘盈数据
		$wen = [];	//盘亏数据
		foreach ($item as $key => $value) {
			$array = array(
					'item_id'	=>$value['item_id'],
					'item'		=>$value['title'],
					'stock'		=>$value['stock'],
					'num'		=>$value['num']
				);
			if( $value['num'] > $value['stock'] ){
				//生成盘盈单商品
				array_push($win, $array);
			}else if( $value['num'] < $value['stock'] ){
				//生成盘亏单商品
				array_push($wen, $array);
			}
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

		    if( count($win) >0 ){
		    	//生成盘盈单
		    	$stockData = array(
		    		'log_id'	=>$data['id'],
		    		'order_sn'	=>'PY'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8),
		    		'shop_id'	=>$info['shop_id'],
		    		'creator_id'=>session('admin_user_auth')['uid'],
		    		'type'	=>1,
		    		'time'	=>time(),
		    		'status'	=>1,
		    		'remarks'	=>$remarks,
		    		'is_admin'	=>1
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
		    		'creator_id'=>session('admin_user_auth')['uid'],
		    		'type'	=>2,
		    		'time'	=>time(),
		    		'status'	=>1,
		    		'remarks'	=>$remarks,
		    		'is_admin'	=>1
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
		return json(['code'=>1,'msg'=>'确认盘点成功']);
	}

	//盘点单的详情
	public function pandian_info(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('单据id错误');
		}
		$Stock = new StockLogModel();
		$list = $Stock ->where('id',$data['id'])->find();
		$this ->assign('list',$list);
		//1级分类
		$typeId = Db::name('item_category')->where(['pid'=>0,'status'=>1,'type'=>1])->field('id,cname')->select();
		$this ->assign('typeId',$typeId);
		return $this ->fetch();
	}

	//盘点单删除
	public function pandian_del()
	{
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('id参数错误');
		}
		$info = DB::name('stock_log')->where('id',$data['id'])->find();
		if( $info['status'] != 1 ){
			$this ->error('只能删除未确认的盘点单');
		}
		// 启动事务
		Db::startTrans();
		try {
		    DB::name('stock_log')->where('id',$data['id'])->delete();
		    Db::name('stock_log_item')->where('log_id',$data['id'])->delete();
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    $this ->error('删除失败');
		}
		$this ->success('删除成功');
	}

	//盘点单的商品列表
	public function pandianGoods(){
		$data = $this ->request ->param();
		$StockItem = new StockLogItemModel();
		$list = $StockItem ->getItemList($data['id'],$data)->select();
		$count = $StockItem ->getItemList($data['id'])->count();
        $result = array("code" => 0, "count" => $count, "data" => $list);
        return json($result);
	}

	//盘赢单
	public function win_list(){
		if ($this->request->isAjax()) {
			$Stock = new StockModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $name = $this ->request->param('name');
            $shop_id = $this ->request->param('shop_id');
            $status = $this ->request->param('status');
            $time = $this ->request->param('time');
            $end_time = $this ->request->param('end_time');
            $type = $this ->request->param('type');

            $where = [];
            if( !empty($name) ){
            	$where[] = ['b.username|b.nickname|a.order_sn','like',"%$name%"];
            }
            if( $shop_id != 0 ){
            	$where[] = ['a.shop_id','=',$shop_id];
            }
            if( $status != 0 ){
            	$where[] = ['a.status','=',$status];
            }
            if( !empty($time) ){
            	$time = strtotime($time);
            	$where[] = ['a.time','>=',$time];
            }
            if( !empty($end_time) ){
            	$end_time = strtotime($end_time);
            	$where[] = ['a.end_time','<=',$end_time];
            }

            $where[] = ['a.status','neq',0];
            $where[] = ['a.type','eq',$type];
            $list = $Stock ->alias('a') 
            		->join('admin b','a.creator_id=b.userid','left')
            		->where($where)
            		->page($page,$limit)
            		->field('a.*')
            		->order('time desc')
            		->select();
            foreach ($list as $key => $value) {
            	if( $value['is_admin'] == 1 ){
            		$value['creator_id'] = Db::name('admin')->where('userid',$value['creator_id'])->value('username');
            	}else{
            		$value['creator_id'] = Db::name('shop_worker')->where('id',$value['creator_id'])->value('name');
            	}
            	if( $value['end_time'] != 0 ){
					$list[$key]['stime'] = date('Y-m-d H:i:s',$value['end_time']);
				}else{
					$list[$key]['stime'] = date('Y-m-d H:i:s',$value['time']);
				}
            }
            $total =  $Stock
            		  ->join('admin b','a.creator_id=b.userid','left')
            		  ->alias('a') 
            		  ->where($where)
            		  ->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		//仓库
		$Shop = new ShopModel();
		$shop = $Shop ->where(['status'=>1])->field('id,name')->select();
		$this ->assign('shop',$shop);
		return $this->fetch();
	}

	//盘亏单
	public function wane_list(){
		//仓库
		$Shop = new ShopModel();
		$shop = $Shop ->where(['status'=>1])->field('id,name')->select();
		$this ->assign('shop',$shop);
		return $this->fetch();
	}

	//盘盈/盘亏单 详情
	public function info(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('单据id错误');
		}
		$Stock = new StockModel();
		$list = $Stock ->where('id',$data['id'])->find();
		$this ->assign('list',$list);
		//1级分类
		$typeId = Db::name('item_category')->where(['pid'=>0,'status'=>1,'type'=>1])->field('id,cname')->select();
		$this ->assign('typeId',$typeId);
		return $this ->fetch();
	}

	//盘盈/盘亏单 商品详情
	public function goodsinfo(){
		$data = $this ->request ->param();
		$StockItem = new StockItemModel();
		$list = $StockItem ->getItemList($data['id'],$data)->select();
		// dump($list);die;
		$count = $StockItem ->getItemList($data['id'])->count();
        $result = array("code" => 0, "count" => $count, "data" => $list);
        return json($result);
	}

	//盘盈，盘亏单的编辑
	public function edit_tt(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('单据id错误');
		}
		$Stock = new StockModel();
		$list = $Stock ->where('id',$data['id'])->find();
		$this ->assign('list',$list);
		//1级分类
		$typeId = Db::name('item_category')->where(['pid'=>0,'status'=>1,'type'=>1])->field('id,cname')->select();
		$this ->assign('typeId',$typeId);
		return $this ->fetch();
	}

	//盘盈/盘亏单 商品详情的修改备注
	public function editItemRemrks(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			return json(['code'=>0,'msg'=>'修改失败,id参数错误','data'=>'']);
		}
		$StockItem = new StockItemModel();
		$res = $StockItem ->where('id',$data['id'])->update(['remarks'=>$data['remarks']]);
		if( $res ){
			return json(['code'=>1,'msg'=>'修改成功','data'=>'']);
		}else{
			return json(['code'=>0,'msg'=>'修改失败，请刷新页面重试','data'=>'']);
		}
	}

	//盘盈/盘亏单 商品详情的修改备注
	public function editStockRemrks(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			return json(['code'=>0,'msg'=>'修改失败,id参数错误','data'=>'']);
		}
		$Stock = new StockModel();
		$res = $Stock ->where('id',$data['id'])->update(['remarks'=>$data['remarks']]);
		if( $res ){
			return json(['code'=>1,'msg'=>'修改成功','data'=>'']);
		}else{
			return json(['code'=>0,'msg'=>'修改失败，请刷新页面重试','data'=>'']);
		}
	}

	//盘盈/盘亏单 确认库存
	public function sure_stock(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			$this ->error('单据id错误');
		}
		$Stock = new StockModel();
		$list = $Stock ->where('id',$data['id'])->find();
		$this ->assign('list',$list);
		//1级分类
		$typeId = Db::name('item_category')->where(['pid'=>0,'status'=>1,'type'=>1])->field('id,cname')->select();
		$this ->assign('typeId',$typeId);
		return $this ->fetch();
	}

	//确认库存的操作
	public function sureStock(){
		$data = $this ->request ->param();
		if( empty($data['id']) ){
			return json(['code'=>0,'msg'=>'id参数错误','data'=>'']);
		}
		if( !empty($data['remarks']) ){
			$stockData['remarks'] = $data['remarks'];
		}
		$stockData['status'] = 2;
		$stockData['end_time'] = time();
		$Stock = new StockModel();
		$stocks = Db::name('stock')->where('id',$data['id'])->field('type,shop_id,log_id')->find();
		$StockItem = new StockItemModel();
		$info = $StockItem ->getItemList($data['id'])->select();	//商品列表

		$newArray = [];	//库存调配数据
		foreach ($info as $key => $value) {
			if( $value['stock']>$value['num'] ){
				$t = 2;	//1表示添加库存，反正表示减少库存
				$num = $value['stock']-$value['num'];
			}else{
				$t = 1;
				$num = $value['num']-$value['stock'];
			}
			$array = array(
				'type'	=>$t,	//1表示添加库存，反正表示减少库存
				'num'	=>$num,	//成本表需要增加或修改的数量
				'stock'	=>$value['num'],	//商品最终的数量
				'item_id'	=>$value['item_id'],
				'shop_id'	=>$stocks['shop_id']
			);
			array_push($newArray, $array);
		}
		// 启动事务
		Db::startTrans();
		try {
		    //修改备注，修改状态
			$Stock ->where('id',$data['id'])->update($stockData);
		    //修改库存表
			foreach ($newArray as $key => $value) {
				$siWhere = [];
				$siWhere['shop_id'] = $value['shop_id'];
				$siWhere['item_id'] = $value['item_id'];
				$siList = Db::name('shop_item')->where($siWhere)->find();
				if( $siList ){
					Db::name('shop_item')->where($siWhere)->setField('stock',$value['stock']);
					//判断是否存在多的库存数据数据，如果有多的则删除
                    $emp = [];
                    $emp[] = ['shop_id','eq',$value['shop_id']];
                    $emp[] = ['item_id','eq',$value['item_id']];
                    $emp[] = ['id','neq',$siList['id']];
                    Db::name('shop_item') ->where($emp) ->delete();
				}else{
					Db::name('shop_item')->insert(['shop_id'=>$value['shop_id'],'item_id'=>$value['item_id'],'stock'=>$value['stock']]);
				}
			}
			//修改成本表
			foreach ($newArray as $key => $value) {
				$ppWhere = [];
				$ppWhere[] = ['shop_id','eq',$value['shop_id']];
				$ppWhere[] = ['item_id','eq',$value['item_id']];
				if( $value['type'] == 1 ){
					//表示需要添加库存，则添加最后一条
					$ppList = Db::name('purchase_price')->where($ppWhere)->order('time desc')->find();
					if( $ppList ){
                        Db::name('purchase_price') ->where('id',$ppList['id'])->setInc('stock',$value['num']);
                    }else{
					    $arr = [];
					    $arr = [
					        'shop_id'   =>$value['shop_id'],
					        'type'   =>3,
					        'pd_id'   =>$data['id'],
					        'item_id'   =>$value['item_id'],
					        'md_price'   =>-1,
					        'store_cose'   =>-1,
					        'stock'   =>$value['num'],
					        'time'   =>time(),
					        'sort'   =>0,
                        ];
                        Db::name('purchase_price') ->insert($arr);
                    }
				}else{
					//表示需要减少库存，则减少库存不为0的第一条
					$ppWhere[] = ['stock','>=',0];
					$tt = $this ->getCostPrice($ppWhere,$value['num']);
					foreach ($tt as $k => $v) {
						Db::name('purchase_price') ->where('id',$v['id'])->setDec('stock',$v['num']);
					}
				}
			}

			//查看此盘点单是否全部确认库存完成
			$stockLogInfo = Db::name('stock')->where('log_id',$stocks['log_id'])->field('status')->select();//最多两条记录
			$logType = 0;
			if( count($stockLogInfo) == 1 ){
				if( $stockLogInfo['0']['status'] == 2 ){
					$logType = 1;
				}
			}else{
				if( ($stockLogInfo['0']['status'] ==2) && ($stockLogInfo['0']['status'] == 2) ){
					$logType = 1;
				}
			}
			if( $logType == 1 ){
				//表示都已确认入库
				Db::name('stock_log')->where('id',$stocks['log_id'])->update(['status'=>3,'end_time'=>time()]);
			}

		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    return json(['code'=>'0','msg'=>$e->getMessage()]);
		}
		return json(['code'=>'1','msg'=>'确认库存成功']);
	}

	//统计扣商品成本表的数据
	/**
		$where 需要查询的商品
		$num 此商品一共需要减少的数量

		return $new 需要修改表purchase_price的数据
			id：purchase_price的id
			num: stock需要减少的数量
	*/
	public function getCostPrice($where,$num){
		$list = Db::name('purchase_price')->where($where)->field('id,stock')->order('time asc')->select();
		$new = [];	//需要修改的数据
		foreach ($list as $key => $value) {
			if( $num >$value['stock'] ){
				$array = array(
					'id'	=>$value['id'],
					'num'	=>$value['stock']
				);
				array_push($new, $array);
				$num  = $num - $value['stock'];
			}else{
				$array = array(
					'id'	=>$value['id'],
					'num'	=>$num
				);
				array_push($new, $array);
				break;
			}
		}
		return $new;
	}

	//修改门店成本单价
    public function edit_cost()
    {
        if ( request() ->isPost() )
        {
            $data = $this ->request ->param();
            $res = (new PurchasePrice()) ->edit($data);
            if ( $res )
            {
                return ['code'=>1,'msg'=>'操作成功'];
            }else{
                return ['code'=>0,'msg'=>'操作失败'];
            }
        }else{
            $data = $this ->request ->param();
            $this ->assign('data',$data);
            //门店
            $where[] = ['code','neq',0];
            $where[] = ['status','eq',1];
            $shop = Db::name('shop')->where($where)->field('id,name')->select();
            $this ->assign('shop',$shop);
            return $this ->fetch();
        }
    }
}