<?php

// +----------------------------------------------------------------------
// | 商品分类
// +----------------------------------------------------------------------
namespace app\mall\model\seckill;

use think\Model;
use think\Db;

class FlashSaleModel extends Model
{
    protected $table = 'ddxm_flash_sale';

    public function getStartTimeAttr($val){
        return date('Y-m-d H:i:s',$val);
    }
    public function getEndTimeAttr($val){
        return date('Y-m-d H:i:s',$val);
    }

    /***
     * 拼装多规格
     */
    public function getItemSpecsAttr($val,$data){
        $where = [];
        $where[] = ['item_id','eq',$data['item_id']];
        $where[] = ['flash_sale_id','eq',$data['id']];
        $where[] = ['status','eq',1];
        $item = Db::name('flash_sale_attr') ->where($where)->column('specs_names');
        $tt = '';
        foreach ( $item as $k=>$v ){
            if( $v=='' ){
                $sp = '无';
            }else{
                $sp = $v;
            }
            $tt .= '<p> '.$sp.' </p>';
        }
        return $tt;
    }
    /***
     * 拼装多规格
     */
    public function getOldPriceListAttr($val,$data){
        $where = [];
        $where[] = ['item_id','eq',$data['item_id']];
        $where[] = ['flash_sale_id','eq',$data['id']];
        $where[] = ['status','eq',1];
        $item = Db::name('flash_sale_attr') ->where($where)->column('old_price');
        $tt = '';
        foreach ( $item as $k=>$v ){
            if( $v=='' ){
                $sp = '无';
            }else{
                $sp = $v;
            }
            $tt .= '<p> '.$sp.' </p>';
        }
        return $tt;
    }

    /***
     * 拼装多规格
     */
    public function getPriceListAttr($val,$data){
        $where = [];
        $where[] = ['item_id','eq',$data['item_id']];
        $where[] = ['flash_sale_id','eq',$data['id']];
        $where[] = ['status','eq',1];
        $item = Db::name('flash_sale_attr') ->where($where)->column('price');
        $tt = '';
        foreach ( $item as $k=>$v ){
            if( $v=='' ){
                $sp = '无';
            }else{
                $sp = $v;
            }
            $tt .= '<p> '.$sp.' </p>';
        }
        return $tt;
    }
    /***
     * 拼装多规格
     */
    public function getOverListAttr($val,$data){
        $where = [];
        $where[] = ['item_id','eq',$data['item_id']];
        $where[] = ['flash_sale_id','eq',$data['id']];
        $where[] = ['status','eq',1];
        $item = Db::name('flash_sale_attr') ->where($where)->column('already_num');
        $tt = '';
        foreach ( $item as $k=>$v ){
            $sp = $v;
            $tt .= '<p> '.$sp.' </p>';
        }
        return $tt;
    }
    /***
     * 拼装多规格
     */
    public function getStockListAttr($val,$data){
        $where = [];
        $where[] = ['item_id','eq',$data['item_id']];
        $where[] = ['flash_sale_id','eq',$data['id']];
        $where[] = ['status','eq',1];
        $item = Db::name('flash_sale_attr') ->where($where)->field('stock,already_num,residue_num')->select();
        $tt = '';
        foreach ( $item as $k=>$v ){
//            if( $v['stock']==-1 ){
//                $sp = '不限制';
//            }else{
//                $sp = $v['stock'];
//            }
//            $tt .= '<p> '.$sp.'/'.$v['already_num'].' </p>';

            $tt.= '<p> '.$v['residue_num'].'/'.$v['already_num'].' </p>';
        }
        return $tt;
    }

    /***
     * 获取商品图片
     */
    public function getPicAttr( $val,$data ){
        $pic = Db::name('item')->where('id',$data['item_id'])->value('pic');
        $pic = config('QINIU_URL').$pic;
        $tt = '<img src="'.$pic.'" alt="">';
        return $tt;
    }
}