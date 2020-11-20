<?php
namespace app\shift\model\item;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class ItemCategoryModel extends ShiftbaseModel
{	
	protected $table = 'tf_item_category';
}