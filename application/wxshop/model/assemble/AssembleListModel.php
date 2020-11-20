<?php

namespace app\wxshop\model\assemble;
use think\Model;
use think\Db;

/***
 * 拼团模型
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class AssembleListModel extends Model
{
    protected $table = 'ddxm_assemble_list';

    /***
     * 获取拼团组下面的拼团人数详情
     */
    public function getInfoAttr($val,$data){
        $id = $data['id'];
        $map = [];
        $map[] = ['a.assemble_list_id','eq',$id];
        $map[] = ['a.status','neq',2];
        $list = Db::name('assemble_info')
            ->alias('a')
            ->join('member b','a.member_id=b.id')
            ->join('item c','a.item_id=c.id','left')
            ->join('order o','a.order_id=o.id','left')
            ->where($map)
            ->field('a.id,a.order_id,a.real_price,b.nickname as nickname1,b.wechat_nickname,a.item_id,b.pic,a.member_id,a.commander,a.item_name,c.pic as item_pic,c.mold_id,a.status,o.amount')
            ->select();
        foreach ( $list as $k=>$v ){
            if( !empty($v['wechat_nickname']) ){
                $list[$k]['nickname'] = $v['wechat_nickname'];
            }else{
                $list[$k]['nickname'] = $v['nickname1'];
            }
        }
        return $list;
    }

    /***
     * 获取商品分区
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
}