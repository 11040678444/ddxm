<?php

namespace app\wxshop\model\seckill;
use think\Model;
use think\Db;

/***
 * 抢购模型第二期
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class FlashSaleModel extends Model
{
    protected $table = 'ddxm_flash_sale';

    /***
     * 拼团文案配置
     */
    public function getCopywriting($data){
        $status = [
            0   =>'省钱省得停不下来',
            1   =>'真的超实惠哦',
            2   =>'你知道我在等你吗',
            3   =>'四舍五入等于不要钱',
            4   =>'命中注定一起拼',
            5   =>'捣蛋熊猫超省钱',
            6   =>'风里雨里，我在这里等你',
            7   =>'大家省，才是真的省',
            8   =>'生活不易，且拼且珍惜',
            9   =>'实惠看得见'
        ];
        foreach ( $data as $k=>$v ){
            $data[$k]['copywriting'] = $status[$k];
        }
        return $data;
    }


    /***
     * 判断状态
     */
    public function getStatusAttr($val,$data){
        if( time() >= $data['start_time'] ){
            return  1;      //正在秒杀
        }else{
            return 2;       //还未开始
        }
    }

    /***
     * 获取当前时间
     */
    public function getNowTimeAttr($val,$data){
        return time();
    }

    /***
     * 判断是否抢购完
     */
    public function getIsOverAttr($val,$data){
        if( $data['residue_num'] == '-1' ){
            return 1;   //不限制抢购数量，还未抢购完
        }else {
            if( $data['residue_num'] > 0  ){
                return 1;   //还未抢购完
            }else{
                return 2;   //已抢购光
            }
        }
    }

    /***
     * 获取商品图片
     */
    public function getPicAttr( $val,$data ){
        $pic = Db::name('item')->where('id',$data['item_id'])->value('pic');
        return config('QINIU_URL').$pic;
    }

    /***
     * 添加虚拟已售
     */
    public function getAlreadyNumAttr($val,$data){
        return $data['virtually_num']+$data['already_num'];
    }

    /***start_title
     * 别名开抢标题
     */
    public function getStartTitleAttr($val){
        return date('H:i',$val);
    }
}