<?php
// +----------------------------------------------------------------------
// | 服务卡模型
// +----------------------------------------------------------------------
namespace app\admin\model\excel;
use app\admin\model\Adminlog;
use think\Model;
use think\Db;
class ExcelModel extends Model {
    protected $table = 'ddxm_allot';
    public function MemberExport(){
        $data = db::name("member")
        ->alias("a")
        ->field("a.id,a.nickname,a.mobile,a.shop_code,a.level_id,a.openid,l.level_name,s.name as shop_name,m.money")
        ->join("level l","a.level_id = l.id")
        ->join("shop s","a.shop_code = s.code")
        ->join("member_money m","a.id = m.member_id")
        ->withAttr("openid",function($value,$data){
            if($data['openid']){
                return $data['openid'];
            }
            return "未知";
        })
        ->withAttr("recharge",function($value,$data){
            return Db::name("member_details")->where("member_id",$data['id'])->sum("amount");
        })
        ->order("id","desc")
        ->select();
        return $data;
    }
    public function ticket_consume_details($field){
        $where = [];
        if(isset($field['shop']) && !empty($field['shop'])){
            $where[] = ['a.shop_id',"=",intval($field['shop'])];
        }
        if(isset($field['waiter']) && !empty($field['waiter'])){
            $where[] = ['a.waiter_id',"=",intval($field['waiter'])];
        }
        if(isset($field['service']) && !empty($field['service'])){
            $where[] = ['a.service_id',"=",intval($field['service'])];
        }
        if(isset($field['status']) && !empty($field['status'])){
            $num = $field['status'] -1;
            $where[] = ['a.state',"=",$num];
        }
        $data = db::name("ticket_consumption")
            ->alias("a")
            ->where($where)
            ->field("a.id,a.member_id,a.shop_id,a.service_name,a.service_id,a.waiter_id,a.waiter,a.price,a.state,m.nickname,m.mobile,s.name as shop_name,a.time,a.num")
            ->withAttr("time",function($value,$data){
                return date("Y-m-d H:i:s",$data['time']);
            })
            ->join("member m","a.member_id = m.id")
            ->join("shop s","a.shop_id= s.id")
            ->order("time","desc")
            ->select();
        return $data;
    }
}
