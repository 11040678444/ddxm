<?php


namespace app\stock\model\supplier;

use think\Model;

/**
 * 供应商--
 * Class SupplierModel
 * @package app\stock\model\supplier
 */
class SupplierModel extends Model
{

    protected $table = 'ddxm_supplier';

    // 查询 供应商  所有 列表
    public function getAllList(){
        return $this
            ->field('id,supplier_name')
            ->where('del','0')
            ->order('id desc')
            ->select();
    }

    // 根据ID  查询 供应商 是否  存在
    public function findId($id){
        return $this
            ->field('supplier_name')
            ->where('id',$id)
            ->where('del','0')
            ->find();
    }

    /**
     * 删除信息
     * @param $id
     */
    public function delAd($id)
    {
        try{
            $map['status']=6;
            $this->save($map, ['id' => $id]);
            return ['code' => 200, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}