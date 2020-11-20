<?php

namespace app\stock\controller;

use app\common\controller\Backendbase;
use app\stock\model\warehousing\WarehousingModel;
/**
 * 直接入库控制器
 */
class Warehousing extends Backendbase
{
    /***
     * 入库单列表
     * @return \think\response\Json
     */
    public function get_list(){
        $data = $this ->request->param();
        $list = (new WarehousingModel()) ->getList($data);
        return json($list);
    }

    /***
     * 添加入库单
     */
    public function add(){
        $data = $this ->request ->param();
        $data['admin_id'] = self::getUserInfo()['userid'];
        $res = (new WarehousingModel()) ->add($data);
        return $res;
    }

    /**
     * 转账
     */
    public function transfer(){
        $data = $this ->request ->param();
        if ( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择单据']);
        }
        $rule = '/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/';     //金额规则
        if( !preg_match($rule,$data['amount']) ){
            return json(['code'=>100,'msg'=>'金额出错']);
        }
        $info =  (new WarehousingModel()) ->where('id',$data['id'])->field('id,transfer,transfer_amount')->find();
        if( !$info ){
            return json(['code'=>100,'msg'=>'单据出错']);
        }
        if( $info['transfer'] == 1 ){
            return json(['code'=>100,'msg'=>'该单据已转账了']);
        }
        $update = [
            'transfer'  =>1,
            'transfer_amount'=>$data['amount']
        ];
        $res = (new WarehousingModel()) ->where('id',$data['id'])->update($update);
        if( !$res ){
            return json(['code'=>500,'msg'=>'转账失败']);
        }else{
            return json(['code'=>200,'msg'=>'转账成功']);
        }
    }
}
