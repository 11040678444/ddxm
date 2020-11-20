<?php
/*
	订单控制器
*/
namespace app\index\model\Member;

use think\Model;
use think\Cache;
use think\Db;

class MemberModel extends Model
{
	protected $table = 'ddxm_member';
	//获取会员信息
    public function getMember($id){
    	$data = $this->where("id",intval($id))
                ->field("nickname,mobile,shop_code,level_id as level,regtime as addtime")
                ->find()
                ->append(['level']);
        return $data;
    }

    public function getLevelAttr($val){
        // $val = $data['level_id'];
        return DB::name("member_level")->where("id",$val)->value("level_name");
    }

    public function getAddtimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "暂无";
    }

    public function getShopCodeAttr($val){
        return Db::name("shop")->where("code",$val)->value("name");
    }

    public function getLevelIdAttr($val){
        return Db::name('member_level')->where("id",$val)->value("level_name");
    }

    public function getLevelName($val){
        return Db::name('member_level')->where('id',$val)->value('level_name');
    }

    //根据会员id或者手机号获取会员信息
    public function getMessage($data,$field){
        if( empty($data) ){
            return false;
        }
        if( !empty($data['member_id']) ){
            $where['id'] = $data['member_id'];
        }
        if( !empty($data['mobile']) ){
            $where['mobile'] = $data['mobile'];
        }

        $info = Db::name('member') ->where($where)->find();
        $info['shop_id'] = Db::name('shop')->where('code',$info['shop_code'])->value('id');
        // dump($info);die;
        return $info[$field];

    }

    /**
    查询门店编号
    */
    public function getShopcode($shop_id){
        return Db::name('shop')->where('id',$shop_id)->value('code');
    }

}
