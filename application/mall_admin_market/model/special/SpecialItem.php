<?php

// +----------------------------------------------------------------------
// | 专题模块
// +----------------------------------------------------------------------
namespace app\mall_admin_market\model\special;

use think\Model;
use think\Db;

/**
 * 专题--
 * Class CouponModel
 * @package app\mall_admin_market\model\coupon
 */
class SpecialItem extends Model
{
    protected $table = 'ddxm_st_item';
    /***
     * 添加商品
     */
    public function addOrEdit( $data )
    {
        if( empty($data['item_id']) ){
            return_error('请选择商品');
        }
        if( empty($data['st_id']) ){
            return_error('请选择商品');
        }
        $item_update = [];  //最终的数据
        foreach ( $data['item_id'] as $k=>$v ){
            $arr = [];
            $arr = [
                'st_id' =>$data['st_id'],
                'item_id'   =>$v
            ];
            array_push($item_update,$arr);
        }
        $res = $this ->insertAll($item_update);
        return $res;
    }

    /***
     * 编辑专题商品
     */
    public function editItem( $data )
    {
        if( empty($data['id']) ){
            return_error('请选择商品');
        }
        $res = $this ->where('id',$data['id'])->update($data);
        return $res;
    }


    /***
     * 获取商品列表
     */
    public function getItemList( $data )
    {
        if( !empty($data['page']) && !empty($data['limit']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $where = [];
        if( !empty($data['st_id']) ){
            $where[] = ['a.st_id','eq',$data['st_id']];
        }
        if( !empty($data['hot']) && $data['hot'] == 1 ){
            $where[] = ['a.hot','eq',1];
        }
        if( !empty($data['item_name']) ){
            $where[] = ['i.title','like','%'.$data['item_name'].'%'];
        }
        $list = $this
            ->alias('a')
            ->join('st_type b','a.st_id=b.id')
            ->join('item i','a.item_id=i.id')
            ->where($where)
            ->field('a.id,b.title,a.st_id,a.item_id,a.item_id as item_info,hot')
            ->page($page)
            ->select();
        $count = $this->alias('a')
            ->join('st_type b','a.st_id=b.id')
            ->join('item i','a.item_id=i.id')
            ->where($where)
            ->count();
        return ['data'=>$list,'count'=>$count];
    }

    //获取商品详情
    public function getItemInfoAttr( $val )
    {
        $item = Db::name('item')->field('id,title,min_price,max_price,pic')->where('id',$val)->find();
        $item['pic'] = config('QINIU_URL').$item['pic'];
        return $item;
    }

    //设置商品是否为热门
    public function setHot( $data ){
        if( empty($data['id']) ){
            return_error('请选择商品');
        }
        $info = $this ->where('id',$data['id'])->find();
        if( !$info ){
            return_error('ID错误');
        }
        $update_data['hot'] = $info['hot']==1?2:1;
        $res = $this ->where('id',$data['id'])->update($update_data);
        return $res;
    }

}