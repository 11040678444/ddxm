<?php
namespace app\shift\model\member;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class MemberModel extends ShiftbaseModel
{	
	protected $table = 'tf_member';
}