<?php

namespace app\wxshop\model\assemble;
use think\Model;
use think\Db;

/***
 * 拼团模型
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class AssembleModel extends Model
{
    protected $table = 'ddxm_assemble';

    /***
     * 获取列表的已拼团件数
     */
    public function getAssembleNumAttr($val,$data){
        $id = $data['id'];
        $map = [];
        $map[] = ['assemble_id','eq',$id];
        $map[] = ['status','eq',2];
        $list = Db::name('assemble_list') ->where($map)->column('num');
        $count = 0;
        foreach ($list as $v){
            $count += $v;
        }
        return $count;
    }

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
    public function getPicsAttr($val){
        if( $val == '' ){
            return [];
        }
        $val = explode(',',$val);
        $res = [];
        foreach ($val as $k=>$v){
            $res[] = "http://picture.ddxm661.com/".$v;
        }
        return $res;
    }
    public function getVideoAttr($val){
        if( $val == '' ){
            return '';
        }
        return "http://picture.ddxm661.com/".$val;
    }

    /***
     * 分区
     * @param $val
     * @param $data
     * @return mixed|string
     */
    public function getMoldAttr($val,$data){
        $val = $data['mold_id'];
        $title = Db::name('item_type')->where('id',$val)->value('title');
        if( $title ){
            return $title;
        }
        return '熊猫自营';
    }

    /***
     * 分区
     * @param $val
     * @param $data
     * @return mixed|string
     */
    public function getSalesAttr($val,$data){
        return $data['all_stock'] - $data['remaining_stock'];
    }

    /***
     * item_service_ids
     * @param $val
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getItemServiceIdsAttr($val){
        if( $val == '' || $val==null ){
            return [];
        }
        $where = [];
        $where[] = ['id','in',$val];
        $list = Db::name('item_ensure')->where($where)->order('sort asc')->field('id,title,content')->select();
        return $list;
    }

    /***
     * 1:正在抢购中，2即将开始，3已结束
     * 获取是否开始
     * return begin
     */
    public function getBeginAttr($val,$data){
        $startTime = $data['begin_time'];
        $endtTime = $data['end_time'];
        if( time() >$endtTime ){
            return 3;       //已结束
        }
        if( time() <$startTime ){
            return 2;       //即将开始
        }
        if( ($startTime<=time()) && ($endtTime>=time()) ){
            return 1;       //正在拼团中
        }
    }

    public function getAllPeopleAttr($val,$data){
        $assemble_id = $data['id'];
        $count = Db::name('assemble_list')->where(['assemble_id'=>$assemble_id,'status'=>2])->sum('num');
        return $count;
    }
}