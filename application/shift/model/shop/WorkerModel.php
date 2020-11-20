<?php
namespace app\shift\model\shop;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class WorkerModel extends ShiftbaseModel
{	
	protected $table = 'tf_worker';
}