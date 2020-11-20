<?php
namespace app\shift\model\service;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class CardModel extends ShiftbaseModel
{	
	protected $table = 'tf_member_card';

	public function getCard(){
		return $this->alias('b')
            ->order('b.addtime desc')
            ->field('b.*');
	}
}