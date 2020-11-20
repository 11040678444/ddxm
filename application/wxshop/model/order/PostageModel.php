<?php
/*
    订单模型
*/
namespace app\wxshop\model\order;
use think\Model;
use think\Cache;
use think\Db;

class PostageModel extends Model
{
    protected $table = 'ddxm_postage';
    public function getChildAttr($val,$data){
        $postageId = $data['id'];
        $data = Db::name('postage_info')
            ->where('postage_id',$postageId)
            ->field('id,area_ids,first,first_price,two,two_price')
            ->select();
        return $data;
    }
}