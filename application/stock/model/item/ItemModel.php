<?php

// +----------------------------------------------------------------------
// | 商品
// +----------------------------------------------------------------------
namespace app\stock\model\item;

use think\Model;
use think\Db;

class ItemModel extends Model
{
    protected $table = 'ddxm_item';

    /***
     * 搜索商品列表1：搜索线上商品列表
     */
    public function getOnlineItemList($data){
        $where = [];
        if( !empty($data['brand_id']) ){
            $where[] = ['a.brand_id','eq',$data['brand_id']];
        }
        if( !empty($data['item_name']) ){
            $where[] = ['a.title','like','%'.$data['item_name'].'%'];
        }
        if( !empty($data['bar_code']) ){
            $name = $data['bar_code'];
        }else{
            $name = '';
        }
        $where[] = ['a.item_type','eq',1];
        if( !empty($data['shop_id']) ){
            $where[] = ['b.shop_id','eq',$data['shop_id']];
            $joinType = 'INNER';
        }else{
            $joinType = 'LEFT';
        }
        if( isset($data['stock']) && ($data['stock'] != '') ){
            //库存大于0
            if( $data['stock'] == 1 ){
                $where[] = ['b.stock','>',0];
            }else if( $data['stock'] == 0 ){
                $where[] = ['b.stock','eq',0];
            }
        }
        if( isset($data['not_ids']) && ($data['not_ids']!='') && (count($data['not_ids'])>0) ){
            $where[] = ['a.id','not in',implode(',',$data['not_ids'])];
        }
        if( !empty($data['bar_code']) ){
            if( !empty($data['shop_id']) ){
                $list = $this ->alias('a')
                    ->join('shop_item b','a.id=b.item_id',$joinType)
                    ->where($where)
                    ->where('a.id', 'IN', function($query) use (&$name) {
                        $query->table('ddxm_specs_goods_price')->where('bar_code','eq',$name)
                            ->where('status','eq',1)
                            ->field('gid');
                    });
            }else{
                $list = $this ->alias('a')
                    ->where($where)
                    ->where('a.id', 'IN', function($query) use (&$name) {
                        $query->table('ddxm_specs_goods_price')->where('bar_code','eq',$name)
                            ->where('status','eq',1)
                            ->field('gid');
                    });
            }

        }else{
            if( !empty($data['shop_id']) ){
                $list = $this->alias('a')->join('shop_item b','a.id=b.item_id',$joinType)->where($where);
            }else{
                $list = $this->alias('a')->where($where);
            }

        }
        return $list;
    }

    /***
     * 线上商品展示规格、条形码
     */
    public function getSpecsAttr($val,$data){
        if( $data['item_type'] == 1 ){
            $map = [];
            $map[] = ['gid','eq',$data['id']];
            $map[] = ['status','eq',1];
            $list =  Db::name('specs_goods_price')
                ->where($map)
                ->field('id,key,key_name,bar_code,imgurl as pic,imgurl as pic_url,recommendprice as old_price,price')
                ->select();
            foreach ( $list as $k=>$v ){
                $list[$k]['pic_url'] = config('QINIU_URL').$v['pic_url'];
            }
        }else{
            $list = [];
            $arr = [
                'id'    =>0,
                'key'    =>'',
                'key_name'    =>'',
                'bar_code'    =>$data['bar_code'],
                'pic'    =>$data['pic'],
                'pic_url'    =>!empty($data['pic'])?config('QINIU_URL').$data['pic']:'',
            ];
            array_push($list,$arr);
        }
        return $list;
    }

    /***
     * 搜索商品列表2：搜索门店商品列表
     */
    public function getShopItemList($data){
        $where = [];
        if( !empty($data['item_name']) ){
            $where[] = ['a.title','like','%'.$data['item_name'].'%'];
        }
        if( !empty($data['bar_code']) ){
            $where[] = ['a.bar_code','eq',$data['bar_code']];
        }
        $where[] = ['a.item_type','in','2,3'];
        if( !empty($data['shop_id']) ){
            $where[] = ['b.shop_id','eq',$data['shop_id']];
            $joinType = 'INNER';
        }else{
            $joinType = 'LEFT';
        }
        if( isset($data['stock']) && ($data['stock'] != '') ){
            //库存大于0
            if( $data['stock'] == 1 ){
                $where[] = ['b.stock','>',0];
            }else if( $data['stock'] == 0 ){
                $where[] = ['b.stock','eq',0];
            }
        }
        if( isset($data['not_ids']) && (count($data['not_ids'])>0) ){
            $where[] = ['a.id','not in',implode(',',$data['not_ids'])];
        }
        if( !empty($data['shop_id']) ){
            $list = $this ->alias('a')
                ->join('shop_item b','a.id=b.item_id',$joinType)
                ->where($where);
        }else{
            $list = $this ->alias('a')
                ->where($where);
        }
        return $list;
    }

    public function getPicUrlAttr($val){
        if( empty($val) ){
            return $val;
        }
        return config('QINIU_URL').$val;
    }
}