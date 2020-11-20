<?php

// +----------------------------------------------------------------------
// | 商品分类
// +----------------------------------------------------------------------
namespace app\mall\model\assemble;

use think\Model;
use think\Db;

class AssembleModel extends Model
{
    protected $table = 'ddxm_assemble';

    /***
     * 获取列表完整的数据
     * @param $data
     * @return
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAlldata($data){
        if( count($data) <= 0 ){
            return $data;
        }
        foreach ($data as $k=>$v){
            $where = [];
            $where[] = ['assemble_id','eq',$v['id']];
            $where[] = ['update','eq',$v['update']];
            $info = Db::name('assemble_update') ->where($where)->find();
            $data[$k]['item_name'] = $info['item_name'];
            $data[$k]['old_price'] = $info['old_price'];
            $data[$k]['price'] = $info['price'];
            $data[$k]['commander_price'] = $info['commander_price'];
            $data[$k]['people_num'] = $info['people_num'];
            $data[$k]['buy_num'] = $info['buy_num'];
            $data[$k]['all_stock'] = $info['all_stock'];
            $data[$k]['remaining_stock'] = $info['remaining_stock'];
            $data[$k]['retail'] = $info['retail'];
            $data[$k]['begin_time'] = $info['begin_time'];
            $data[$k]['end_time'] = $info['end_time'];
            $data[$k]['hot'] = $info['hot'];
            $data[$k]['postage_id'] = $info['postage_id'];
        }
        return $data;
    }

    public function getCreateTimeAttr($val){
        if( $val == 0 ){
            return '不限制';
        }
        return date('Y-m-d H:i:s',$val);
    }
    public function getEndTimeAttr($val){
        if( $val == 0 ){
            return '不限制';
        }
        return date('Y-m-d H:i:s',$val);
    }
    public function getBeginTimeAttr($val){
        if( $val == 0 ){
            return '不限制';
        }
        return date('Y-m-d H:i:s',$val);
    }
    public function getHotAttr($val){
        if( $val == 0 ){
            return '不显示';
        }
        return '显示';
    }
}