<?php
// +----------------------------------------------------------------------
// | 服务卡模型
// +----------------------------------------------------------------------
namespace app\admin\model\checkout;
use app\admin\model\Adminlog;
use think\Model;
use think\Db;
class CheckoutitemModel extends Model {
    protected $table = 'ddxm_check_out';
    public function getCreateTimeAttr($val){
        if( $val == 0 ){
            return '时间错误';
        }
        return date('Y-m-d H:i:s',$val);
    }

    //获取门店
    public function getShopNameAttr($val,$data){
        if( empty($data['shop_id']) ){
            return '门店错误';
        }
        return Db::name('shop') ->where('id',$data['shop_id']) ->value('name');
    }

    //获取订单信息
    public function getMessageAttr($val,$data){
        $shop = Db::name('shop')->where('id',$data['shop_id'])->value('name');
        $message = "<p>订单号：".$data['sn']."</p>
				<p>客户名称：".$data['nickname']."</p>
				<p>客户电话：".$data['mobile']."</p>
				<p>总金额：".$data['amount']." 元</p>
				<p>出库门店：".$shop."</p>";
        return $message;
    }

    //获取商品信息
    public function getItemListAttr($val,$data){
        $item = Db::name('check_out_item')->where('check_out_id',$data['id'])->column('title');
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p>".$value."</p>";
        }
        return $tt;
    }

    //获取售价
    public function getPriceListAttr($val,$data){
        $item = Db::name('check_out_item')->where('check_out_id',$data['id'])->column('price');
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p> ￥".$value." 元</p>";
        }
        return $tt;
    }

    //获取商品数量信息
    public function getNumListAttr($val,$data){
        $item = Db::name('check_out_item')->where('check_out_id',$data['id'])->column('num');
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p>".$value."</p>";
        }
        return $tt;
    }

    //获取商品数量信息
    public function getCodeListAttr($val,$data){
        $item = Db::name('check_out_item')->where('check_out_id',$data['id'])->column('bar_code');
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p>".$value."</p>";
        }
        return $tt;
    }

    //总金额
    public function getAmountListAttr($val,$data){
        $tt = '';
        $tt .= "<p>总销售金额：".$data['amount']."</p>";
        $tt .= "<p>总成本金额：".$data['all_cost']."</p>";
        return $tt;
    }

    //获取商品总成本信息
    public function getCostListAttr($val,$data){
        $item = Db::name('check_out_item')->where('check_out_id',$data['id'])->column('allcost');
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p> ￥".$value." 元</p>";
        }
        return $tt;
    }

    //获取总成本
    public function getAllCost($shop_id,$data){
        if( count($data) <= 0 ){
            return [];
        }
        foreach ( $data as $k=>$v ){
            $where = [];
            $where[] = ['shop_id','eq',$shop_id];
            $where[] = ['item_id','eq',$v['id']];
            $where[] = ['stock','>',0];
            $all_list = Db::name('purchase_price') ->where($where) ->field('stock,store_cose')->select();
            $all_cost = 0;  //总成本
            $all_num = 0;   //总库存
            foreach ( $all_list as $key=>$val ) {
                $all_cost = bcadd($all_cost,bcmul($val['stock'],$val['store_cose']),2);
                $all_num = bcadd($all_num,$val['stock'],2);
            }
            $data[$k]['single_cost'] = bcdiv($all_cost,$all_num,2);
            $data[$k]['all_cost'] = $all_cost;
        }
        return $data;
    }

}
