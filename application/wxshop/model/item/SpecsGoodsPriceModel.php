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
class SpecsGoodsPriceModel extends Model
{
    protected $table = 'ddxm_specs_goods_price';

    /***
     * 商品图片
     * @param $val
     * @return string
     */
    public function getPicAttr($val){
        if( $val == '' ){
            return '';
        }
        return "http://picture.ddxm661.com/".$val;
    }

    public function getPicInfoAttr($val){
        if( $val == '' ){
            return [];
        }
        $val = explode(',',$val);
        $info = [];
        foreach ($val as $k=>$v){
            array_push($info,'http://picture.ddxm661.com/'.$v);
        }
        return $info;
    }

    /***
     * 总销量
     * @param $val
     * @param $data
     * @return mixed
     */
    public function getSalesAttr($val,$data){
        return $data['initial_sales']+$data['reality_sales'];
    }
}