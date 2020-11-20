<?php
/*
	订单控制器
*/
namespace app\index\model\Member;

use think\Model;
use think\Cache;
use think\Db;

class MemberRechargeLogModel extends Model
{
	protected $table = 'ddxm_member_recharge_log';

	//会员
	public function getMemberIdAttr($val){
		$member = Db::name('member')->where('id',$val)->field('nickname,mobile')->find();
		return $member['nickname'].'('.$member['mobile'].')';

	}

	//时间
	public function getCreateTimeAttr($val){
		return date('Y-m-d H:i:s',$val);
	}

}