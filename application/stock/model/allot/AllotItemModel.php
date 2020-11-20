<?php

namespace app\stock\model\allot;
use think\Model;
use think\Db;

/**
 * 调拨单--商品
 * Class PurchaseModel
 * @package app\admin\model\purchase
 */
class AllotItemModel extends Model {
    protected $table = 'ddxm_allot_item';

    // 根据采购单ID   得到  采购单商品明细 列表
    public function getAllotIdList($id){
        return $this
            ->field('id as allot_item_id,item_id,amount,remark,num,item as item_name,barcode,attr_name')
            ->where('allot_id',$id)
            ->order('id desc')
            ->select();
    }
}
