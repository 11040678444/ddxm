<?php

namespace app\mall\controller;

use app\wxshop\model\item\ItemModel;
use think\Db;
use think\Controller;

/**
 * 商城配置
 */
class Interfaces
{
    //获取商品详情
    public function getItemInfo($itemId){
        $Item = new ItemModel();
        $where[] = ['id','eq',$itemId];
        $item = $Item ->where($where)
            ->field('id,title,mold_id,type,min_price,max_price,initial_sales,reality_sales,lvid,content,pics,specs_list,item_service_ids')
            ->find()->append(['sales','mold','price']);
        $item['specs_list_info'] = DB::name('specs_goods_price')->where('gid',$item['id'])->select();
        return $item;
    }
}
