<?php

namespace app\stock\model\purchase;
use think\Model;
use think\Db;

/**
 * 采购单--订单明细
 * Class PurchaseModel
 * @package app\admin\model\purchase
 */
class PurchaseItemModel extends Model {
    protected $table = 'ddxm_purchase_item';

    // 根据采购单ID   得到  采购单商品明细 列表
    public function getPurchaseIdList($id){
        return $this
            ->field('id as purchase_item_id,item_id,cg_amount,md_amount,remarks,s_num,num,item_name,item_code,attr_ids,attr_name')
            ->where('purchase_id',$id)
            ->order('id desc')
            ->select();
    }

    /**
     * 采购单编辑
     * @param $addData 编辑时新增的数据
     * @param $upData  编辑数据
     * @return \think\Collection
     * @throws \Exception
     * @throws \think\exception\PDOException
     */
    public function changePurchaseItem($addData,$upData)
    {
        $this->startTrans();

        $res = $this->allowField(true)->saveAll($upData);

        if(!empty($res))
        {
            //编辑的时候新增
            if (!empty($addData))
            {
                $res = $this->allowField(true)->saveAll($addData);
            }
        }
        !empty($res) ? $this->commit() : $this->rollback();

        return $res;
    }
}
