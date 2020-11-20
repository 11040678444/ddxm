<?php

namespace app\wxshop\model\seckill;
use think\Model;
use think\Db;

/***
 * 抢购模型
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class SeckillModel extends Model
{
    protected $table = 'ddxm_seckill';

    /***
     * 获取开始时间
     * @param $val
     * @param $data
     * @return false|string
     */
    public function getStartAttr($val,$data){
        $startTime = $data['start_time'];
        return date('H:i',$startTime);
    }

    /***
     * 1:正在抢购中，2即将开始，3已结束
     * 获取是否开始
     */
    public function getBeginAttr($val,$data){
        $startTime = $data['start_time'];
        $endtTime = $data['end_time'];
        if( time() >$endtTime ){
            return 3;       //已结束
        }
        if( time() <$startTime ){
            return 2;       //即将开始
        }
        if( ($startTime<=time()) && ($endtTime>=time()) ){
            return 1;       //正在抢购中
        }
    }

    /***
     * 获取商品图片
     */
    public function getPicAttr($val){
        return config('QINIU_URL').$val;
    }

    /***
     * 获取抢购类型：1正常，2已抢光
     */
    public function getNumTypeAttr($val,$data){
        if( $data['store'] != '-1' ){
            if( $data['num'] != 0 ){
                if( ($data['already_num'] >= $data['num']) || ($data['already_num']>=$data['store']) ){
                    return 2;
                }else if( ($data['already_num']<$data['num']) && ($data['already_num']<$data['store']) ){
                    return 1;
                }
            }else{
                if( $data['already_num'] >=$data['store'] ){
                    return 2;
                }else{
                    return 1;
                }
            }

        }else{
            if( $data['num'] !=0 ){
                if( $data['already_num'] >= $data['num'] ){
                    return 2;
                }else{
                    return 1;
                }
            }else{
                return 1;
            }

        }
    }

    /***
     * 结束时间
     */
    public function getEndOrStartAttr($val,$data){
        $endTime = $data['end_time'];
        $startTime = $data['start_time'];
        if( (time() >= $startTime) && (time()<=$endTime) ){
            //正在抢购中
            return ($endTime-time());   //结束倒计时
        }else if( time()<$startTime ){
            //即将开始
            return  $startTime-time();     //开始倒计时
        }else if(time()>$endTime) {
            return '';
        }
    }

    /***
     * 服务器当前时间
     */
    public function getNowTimeAttr($val,$data){
        return time();
    }

    /***
     * 详情图
     */
//    public function getContentAttr($val){
//        $content = explode(',',$val);
//        $info = [];
//        foreach ($content as $k=>$v){
//            array_push($info,config('QINIU_URL').$v);
//        }
//        return $info;
//    }
    /***
     * 详情图
     */
    public function getPicsAttr($val){
        $content = explode(',',$val);
        $info = [];
        foreach ($content as $k=>$v){
            array_push($info,config('QINIU_URL').$v);
        }
        return $info;
    }
    /***
     * item_service_ids
     * 服务详情
     */
    public function getItemServiceIdsAttr($val){
        if( $val == '' ){
            return [];
        }
        $map[] = ['id','in',$val];
        $list = Db::name('item_ensure')->where($map)->order('sort asc')->field('title,content')->select();
        return $list;
    }

    /****
     * 获取分区（类型）
     */
    public function getMoldAttr($val,$data){
        $moldId = $data['mold_id'];
        $val = Db::name('item_type')->where('id',$moldId)->value('title');
        if( $val ){
            return $val;
        }
        return '';
    }
}