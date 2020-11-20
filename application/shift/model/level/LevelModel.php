<?php
namespace app\shift\model\level;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class LevelModel extends ShiftbaseModel
{	
	protected $table = 'tf_level';
}