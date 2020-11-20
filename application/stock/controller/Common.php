<?php
namespace app\stock\controller;

use think\Controller;
use think\Db;
use app\stock\model\item\ItemModel;
/**
进销存公用控制器
 */
class Common extends Controller
{
    /***
     * 搜索商品
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_item(){
        $data = $this ->request->param();
        if( empty($data['item_type']) ){
            return json(['code'=>100,'msg'=>'请选择搜索线上还是门店商品']);
        }
        $page = !empty($data['page'])?$data['page']:1;
        $limit = !empty($data['limit'])?$data['limit']:10;
        if( !empty($data['shop_id']) ){
            $field = 'a.id,a.title,a.item_type,a.pic,a.pic as pic_url,b.stock';
        }else{
            $field = 'a.id,a.title,a.pic,a.pic as pic_url,a.item_type';
        }
        if( $data['item_type'] == 1 ){
            $list = (new ItemModel()) ->getOnlineItemList($data)->page($page,$limit)
                ->field($field)->select()->append(['specs'])->toArray();
            $count = (new ItemModel()) ->getOnlineItemList($data)->count();
        }else{
            $list = (new ItemModel()) ->getShopItemList($data)->page($page,$limit)
                ->field($field)->select()->append(['specs'])->toArray();
            $count = (new ItemModel()) ->getShopItemList($data)->count();
        }
        if( !$list ){
            return json(['code'=>100,'msg'=>'获取失败']);
        }
        return json(['code'=>200,'count'=>$count,'msg'=>'获取成功','data'=>$list]);
    }
}