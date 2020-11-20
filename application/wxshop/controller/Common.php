<?php
namespace app\wxshop\controller;

use think\Controller;
use think\Db;
use app\common\model\UtilsModel;
/**
商城公共方法
 */
class Common extends Controller
{
    /***
     * 查看是否库存无货
     * $data 里面全是order_id
     */
    public function getItemStore($data)
    {
        $where[] = ['a.order_id','in',$data];
        $orderItem = Db::name('order_goods')
            ->leftJoin('specs_goods_price b','a.item_id=b.gid and a.attr_ids=b.key and b.status=1')
            ->join('order o','a.order_id=o.id')
            ->join('item i','a.item_id=i.id')
            ->alias('a')
            ->where($where)
            ->field('a.item_id,a.attr_ids,a.num,b.key_name as attr_name,b.bar_code,b.imgurl as pic,o.sn as order_sn,i.sender_id as sup_id,i.title')->select();
        if ( !$orderItem )
        {
            return ['code'=>300,'msg'=>'error'];
        }
        $orderItem = json_encode($orderItem);
        $url = 'http://testadmin2.ddxm661.com/api/Purchase_goods/addBePurchase';
        $res = (new UtilsModel()) ->httpPost($url,['items'=>$orderItem]);
        return $res;
    }
}