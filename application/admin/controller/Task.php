<?php

namespace app\admin\controller;

use app\admin\model\allot\AllotModel;
use think\Controller;
use think\Db;
use think\Query;
/**
 * 出库管理模块
 */
class Task extends Controller
{
    //调拨单定时器
    public function allot_timer(){
        $time = strtotime("-2 day");
        $where = [];
        $where[] = ['status','eq',1];
        $where[] = ['out_time','<',$time];
        $where[] = ['in_shop','not in ','48,50'];
        $data = db::name("allot")->where($where)->select();
        $allotmodel = new AllotModel();
        foreach($data as $key=>$val){
            $allot =[
                "in_admin_id"=>1,
                "in_admin_user"=>"自动入库",
                "in_time"=>time(),
                "status"=>2,
            ];
            $res = db::name("allot")->where("id",$val['id'])->update($allot);
            if( $res ){
                $allotmodel->allot_timer($val['id']);
            }
        }
    }
}