<?php

// +----------------------------------------------------------------------
// | 商品分类
// +----------------------------------------------------------------------
namespace app\mall\model\items;

use think\Model;
use think\Db;

class ItemModel extends Model
{
    protected $table = 'ddxm_item';

    public function getTimeAttr($val){
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

    public function getPicSrcAttr($val,$data){
        $pic = $data['pic'];
        if( $pic == '' ){
            return '';
        }
        return '<img src="http://picture.ddxm661.com/'.$pic.'" alt="'.$data['title'].'">';
    }

    /***
     * @param $val
     * @return mixed
     */
    public function getTypeIdAttr($val){
        return Db::name('item_category')->where('id',$val)->value('cname');
    }

    /***
     * @param $val
     * @return mixed
     */
    public function getTypeAttr($val){
        return Db::name('item_category')->where('id',$val)->value('cname');
    }

    /***
     * @param $val
     * @return mixed
     */
    public function getUnitIdAttr($val){
        return Db::name('item_unit')->where('id',$val)->value('title');
    }

    /***
     * @param $val
     * @return mixed
     */
    public function getMoldIdAttr($val){
        return Db::name('item_type')->where('id',$val)->value('title');
    }

    //创建者
    public function getUserIdAttr($val){
        if( $val==0 ){
            return '0';
        }
        return Db::name('admin')->where('userid',$val)->value('username');
    }

    public function getStoresAttr($val,$data){
        $store = $data['store'];
        if( $store == -1 ){
            return '无限制';
        }
        return $data['store'];
    }

    /***
     * 拼接商品规格信息
     */
    public function getSpecsAttr($val,$data){
        $where = [];
        $where[] = ['gid','eq',$data['id']];
        $where[] = ['status','eq',1];
        $specs = Db::name('specs_goods_price') ->where($where) ->select();
        $specsData = [];
        if( count($specs) >1 ){
            foreach ($specs as $key => $value) {
                $specsData['key_name'] .= "<p> ".$value['key_name']." </p>";
            }
        }else{
            $specsData['key_name'] = "<p>无</p>";   //名称
        }
        foreach ($specs as $key => $value) {
            $specsData['yuanjia'] .= "<p> ￥".$value['recommendprice']." 元</p>";   //原价
            $specsData['price'] .= "<p> ￥".$value['price']." 元</p>";   //原价
            if( empty($value['bar_code']) ){
                $value['bar_code'] = '无';
            }
            $specsData['bar_code'] .= "<p> ".$value['bar_code']." </p>";   //原价
        }
        return $specsData;
    }

    public function zuItem($data){
        if( count($data) <=0 ){
            return [];
        }
        foreach ( $data as $k=>$v ){
            $data[$k]['key_name'] = $v['specs']['key_name'];
            $data[$k]['yuanjia'] = $v['specs']['yuanjia'];
            $data[$k]['price'] = $v['specs']['price'];
            $data[$k]['bar_code'] = $v['specs']['bar_code'];
        }
        return $data;
    }

    public function getBrandAttr($val,$data){
        if( $data['brand_id'] == '' ){
            return '无';
        }
        return Db::name('brand') ->where('id',$data['brand_id']) ->value('title');
    }

    //获取规格
    public function getKeyAttr($val,$data){
        $where = [];
        $where[] = ['gid','eq',$data['id']];
        $where[] = ['status','eq',1];
        $list = Db::name('specs_goods_price')
            ->where($where)
            ->field('gid,key,key_name,price,store,recommendprice')
            ->select();
        $specs = '';        //规格
        $prices = '';       //原金额
        $keys = '';       //原金额
        $keyNames = '';
        $stores = '';       //库存

        foreach ( $list as $k=>$v ){
            if( empty($v['key_name']) ){
                $key_name = '无';
            }else{
                $key_name = $v['key_name'];
            }
            if( empty($v['store']) || $v['store'] == '-1' ){
                $store = '不限制';
            }else{
                $store = $v['store'];
            }
//            $keyNames .= '<p>'.$key_name.'<p>';
            $prices .= '<p>'.$v['recommendprice'].'<p>';
            $stores .= '<p>'.$store.'<p>';
            $keyNames .='<input type="checkbox" checked name="'.$v['recommendprice'].'" lay-filter="nihao" id="'.$store.'"
            class="'.$v['gid'].'" title="'.$key_name.'" value="'.$v['key'].'"> <br />';
//            $keyNames .='<input type="checkbox" checked name="ratio_type"  class="'.$v['gid'].'" value="" title="'.$key_name.'"> <br />';
        }
        return ['keyNames'=>$keyNames,'prices'=>$prices,'stores'=>$stores];
    }

}