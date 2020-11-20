<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\warehousing;

use think\Model;
use think\Db;

class WarehousingModel extends Model
{
	protected $table = 'ddxm_warehousing';

	//拼装入库信息
	public function getMessageAttr($val,$data){
		$status = $data['status'];
		$supplier = Db::name('supplier')->where('id',$data['supplier_id'])->value('supplier_name');
		$shop = Db::name('shop')->where('id',$data['shop_id'])->value('name');
		$user = Db::name('admin')->where('userid',$data['user_id'])->value('username');
		$quser = Db::name('admin')->where('userid',$data['quser_id'])->value('username');
		if( $status == 1 ){
			$message = "<p>订单号：{$data['sn']}</p>
				<p>供应商：".$supplier."</p>
				<p>所入仓库：".$shop."</p>
				<p>入库人：".$user."</p>
				<p>入库时间：".date('Y-m-d H:i:s',$data['create_time'])."</p>";
		}else{
			$message = "<p>订单号：{$data['sn']}</p>
				<p>供应商：".$supplier."</p>
				<p>所入仓库：".$shop."</p>
				<p>入库人：".$user."</p>
				<p>入库时间：".date('Y-m-d H:i:s',$data['create_time'])."</p>
				<p>取消入库人：".$quser."</p>
				<p>取消入库时间：".date('Y-m-d H:i:s',$data['store_time'])."</p>";
		}
		return $message;
	}

	//拼接商品信息
	public function getItemListAttr($val,$data){
		$item = Db::name('warehousing_item')->where('warehousing_id',$data['id'])->column('item_name');
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p>".$value."</p>";
		}
		return $tt;
	}

    //拼接条形码
    public function getBarcodeListAttr($val,$data){
        $item = Db::name('warehousing_item')->where('warehousing_id',$data['id'])->column('bar_code');
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p>".$value."</p>";
        }
        return $tt;
    }


	//拼接入库单价信息
	public function getPriceListAttr($val,$data){
		$item = Db::name('warehousing_item')->where('warehousing_id',$data['id'])->column('price');
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p>".$value."</p>";
		}
		return $tt;
	}

	//拼接入库数量信息
	public function getNumListAttr($val,$data){
		$item = Db::name('warehousing_item')->where('warehousing_id',$data['id'])->column('num');
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p>".$value."</p>";
		}
		return $tt;
	}

	//拼接合计金额信息
	public function getAllpriceListAttr($val,$data){
		$item = Db::name('warehousing_item')->where('warehousing_id',$data['id'])->column('all_price');
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p>".$value."</p>";
		}
		return $tt;
	}
}