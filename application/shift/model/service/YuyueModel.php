<?php
namespace app\shift\model\service;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class YuyueModel extends ShiftbaseModel
{
    protected $table = 'tf_yuyue';


    /***
     * 获取使用次数
     * @param $user_card_id
     * @param $serviceId
     * @return float|string
     */
    public  function getCishu($user_card_id,$serviceId){
        $service_sid = Db::name('service')->where('id',$serviceId)->value('s_id');
        return $this ->where(['user_card_id'=>$user_card_id,'type'=>$service_sid])->count();
    }

    /***
     * 获取消费明细
     * @param $user_card_id
     * @param $serviceId
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getxiao($user_card_id,$serviceId){
        $service_sid = Db::name('service')->where('id',$serviceId)->value('s_id');
        return $this ->where(['user_card_id'=>$user_card_id,'type'=>$service_sid])->select();
    }
}