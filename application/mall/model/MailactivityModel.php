<?php


namespace app\mall\model;

use think\Model;

/**
 * 充值累积送   model
 * Class MailactivityModel
 * @package app\mall\model
 */
class MailactivityModel extends  Model
{

    protected $table = 'ddxm_online_activity';

    protected $autoWriteTimestamp = true;

    // 活动列表
    public function getList($limit,$page){

        $list = $this
            ->field('oa.id,sp.name as shopname,tc.card_name,oa.type,oa.price,oa.create_time,oa.status')
            ->alias('oa')
            ->join('shop sp','sp.id=oa.shop_id')
            ->join('ddxm_ticket_card tc','tc.id=oa.activity_type_id')
            ->page($page,$limit)->order('oa.id desc')->select();
        return $list;
    }

    public function getCreateTimeAttr($val){
        return date('Y-m-d H:i:s',$val);
    }
    // 获取 所有数据条数
    public function getListCount(){
        return $this->count();
    }

    // 添加
    public function add($param){
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * 根据id获取一条信息
     * @param $id
     */
    public function getOneAd($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * 编辑信息
     * @param $param
     */
    public function editAd($param)
    {
        try{

            $result = $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}