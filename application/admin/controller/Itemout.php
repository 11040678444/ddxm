<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\itemout\OrderModel;
use app\admin\model\itemout\OrderRefundModel;
use think\Db;


/**
 * 出库管理->出库单，会员退货单
 */
class Itemout extends Adminbase
{
	//出库单
	public function index(){
		if ($this->request->isAjax()) {
			//先不支持商品名称搜索
            $Order = new OrderModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $data = $this ->request->param();
            $list = $Order->getOrderList($data)
                        ->page($page,$limit)
                        ->order('add_time desc')
                        ->select()
                        ->append(['message','item_list','price_list','num_list','all_price_list','cost_list','out_shop','member_info']);
            $total = $Order->getOrderList($data)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        $shopWhere[] = ['code','neq',0];
		$shopWhere[] = ['status','=',1];
		$shop = Db::name('shop')->where($shopWhere)->field('id,name')->select();
        $this ->assign('shop',$shop);
        return $this->fetch();
	}



	//会员退货单
	public function return_item(){
		if ($this->request->isAjax()) {
			//先不支持商品名称搜索
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $data = $this ->request->param();
            $data = $this ->request ->param();
			$Order = new OrderRefundModel();
			$list = $Order->getOrderList($data)->select()
                        ->append(['message','item_list','price_list','member','num_list','all_price','shop']);
			// dump($list);die;
            $total = $Order->getOrderList($data)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        $shopWhere[] = ['code','neq',0];
		$shopWhere[] = ['status','=',1];
		$shop = Db::name('shop')->where($shopWhere)->field('id,name')->select();
        $this ->assign('shop',$shop);
        return $this->fetch();
	}
}