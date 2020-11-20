<?php
namespace app\shift\model\service;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class ServiceCardModel extends ShiftbaseModel
{	
	protected $table = 'tf_service_card';
}