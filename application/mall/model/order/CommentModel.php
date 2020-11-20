<?php
// +----------------------------------------------------------------------
// | 商城订单模型
// +----------------------------------------------------------------------
namespace app\mall\model\order;
use app\admin\model\Adminlog;
use think\Model;
use think\Db;
class CommentModel extends Model {
    protected $table = 'ddxm_comment';
    public function index(){
        return $this->table($this->table)->field("id,item_id as item_title,specs,member_id,member_id as nickname,add_time,status,status as state,level,pic,level as levels,comment,remark,operator,operator_time");
    }
    public function getItemTitleAttr($val){
        return db::name("item")->where("id",$val)->value("title");
    }
    public function getPicAttr($val){
        if($val){
            $val = explode(",",$val);
            $data = [];
            foreach($val as $key=>$value){
//                $data[] = "http://picture.ddxm661.com/".$value;
                $data[] = $value;
            }
            return $data;
        }
        return "";
    }
    public function getOperatorTimeAttr($val){
        return date("Y-m-d H:i:s",$val);
    }
    public function getNicknameAttr($val){
        if($val==0){
            return "匿名用户";
        }
        $list = db::name("member")->where("id",$val)->field("nickname,wechat_nickname")->find();
        return !empty($list['wechat_nickname'])?$list['wechat_nickname']:$list['nickname'];
    }
    public function getAddTimeAttr($val){
        return date("Y-m-d H:i:s",$val);
    }
    public function getStatusAttr($val){
        $data = [
            0=>"待审核",
            1=>"已通过",
            2=>"已拒绝",
        ];
        return $data[$val];
    }
    public function getLevelAttr($val){
        $data = [
            1=>"一星",
            2=>"二星",
            3=>"三星",
            4=>"四星",
            5=>"五星",
        ];
        return $data[$val];
    }
}
