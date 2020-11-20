<?php
namespace app\shift\model\item;

use app\shift\model\SmallbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class ItemsamllModel extends SmallbaseModel
{
    protected $table = 'ddxm_item';

    //获取商品规格数据
    public function getSpecsAttr($val,$data)
    {
        $db = Db::connect($this->connection);
        $item_id = $data['id'];
        $map = [];
        $list = $db->name('specs_goods_price')
            ->where('gid',$item_id)
            ->select();
        return $list;
    }

    //获取拼团活动
    public function getAssemble(){
        $db = Db::connect($this->connection);
        $list = $db ->name('assemble') ->select();
        $list = self::getAssembleAttr($list);
        return $list;
    }

    //获取拼团活动的attr
    public function getAssembleAttr($data){
        $db = Db::connect($this->connection);
        $ids = [];
        foreach ($data as $k=>$v){
            $data[$k]['attr'] = [];
            $ids[] = $v['id'];
        }
        $ids = implode(',',$ids);
        $where[] = ['assemble_id','in',$ids];
        $tt = $db->name('assemble_attr')->where($where)->select();
        foreach ($data as $k=>$v){
            foreach ($tt as $k1=>$v1){
                if( $v['id'] == $v1['assemble_id'] ){
                    array_push($data[$k]['attr'],$v1);
                }
            }
        }
        return $data;
    }

    //获取秒杀商品
    public function getSeckill(){
        $db = Db::connect($this->connection);
        $list = $db ->name('seckill') ->select();
        return $list;
    }

    //获取分类
    public function getCatrgory(){
        $db = Db::connect($this->connection);
        $list = $db->name('item_category')->where('online',1)->select();
        return $list;
    }
}