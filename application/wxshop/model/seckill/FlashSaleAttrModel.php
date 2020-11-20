<?php

namespace app\wxshop\model\seckill;
use think\Model;
use think\Db;

/***
 * 抢购副表模型第二期
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class FlashSaleAttrModel extends Model
{
    protected $table = 'ddxm_flash_sale_attr';

    /**
     * 获取规格的图片
     */
    public function getPicAttr( $val,$data){
        $where = [];
        $where[] = ['key','eq',$data['specs_ids']];
        $where[] = ['gid','eq',$data['item_id']];
        $where[] = ['status','eq',1];
        $pic = Db::name('specs_goods_price') ->where($where)->value('imgurl');
        return config('QINIU_URL').$pic;
    }
    /***
     * 添加虚拟已售
     */
    public function getAlreadyNumAttr($val,$data){
        return $data['virtually_num']+$data['already_num'];
    }
}