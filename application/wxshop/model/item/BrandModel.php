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
class BrandModel extends Model
{
    protected $table = 'ddxm_brand';

    public function getThumbAttr($val){
        if( $val == '' ){
            return '';
        }
        return "http://picture.ddxm661.com/".$val;
    }
}