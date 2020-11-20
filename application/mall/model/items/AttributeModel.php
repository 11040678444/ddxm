<?php

// +----------------------------------------------------------------------
// | 商品属性
// +----------------------------------------------------------------------
namespace app\mall\model\items;

use think\Model;
use think\Db;

class AttributeModel extends Model
{
    protected $table = 'ddxm_item_attribute';

    public function getCreateTimeAttr($val){
        if( $val == 0 ){
            return 0;
        }else{
            return date('Y-m-d H:i:s',$val);
        }
    }

    public function getUpdateTimeAttr($val){
        if( $val == 0 ){
            return 0;
        }else{
            return date('Y-m-d H:i:s',$val);
        }
    }

    //更新人
    public function getUpdateIdAttr($val){
        if( $val==0 ){
            return '0';
        }
        return Db::name('admin')->where('userid',$val)->value('username');
    }

    /***
     * 获取分类名称
     * @param $val
     * @param $data
     * @return mixed
     */
    public function getCategoryNameAttr($val, $data)
    {
        return Db::name('item_category')->where('id',$data['category_id'])->value('cname');
    }

    public function getTypeIdAttr($val,$data){
        return Db::name('item_category')->where('id',$data['category_id'])->value('pid');
    }

    /***
     *
     * @param int $pid
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAttrList($category_id,$pid = 0){
        $list = $this ->where(['pid'=>$pid,'category_id'=>$category_id,'status'=>1])->select();
        foreach ($list as $k=>$v){
            $data = $this ->getAttrList($v['category_id'],$v['id']);
            if( count($data)>0 ){
                $list[$k]['child'] = $data;
            }
        }
        return $list;
    }

}