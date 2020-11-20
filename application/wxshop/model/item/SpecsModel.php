<?php
/*
    股东数据统计表
*/
namespace app\wxshop\model\item;
use think\Model;
use think\Db;

/***
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class SpecsModel extends Model
{
    protected $table = 'ddxm_item_specs';

    /***
     * 获取上级的名称
     * @param $val
     * @param $data
     * @return mixed|string
     */
    public function getSuperiorAttr($val,$data){
        $id = $data['pid'];
        $parent = $this ->where('id',$id)->value('title');
        if( !$parent ){
            return '';
        }
        return $parent;
    }
}