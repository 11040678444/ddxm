<?php

// +----------------------------------------------------------------------
// | 协议
// +----------------------------------------------------------------------
namespace app\mall\model\setting;

use think\Model;
use think\Db;

class SettingModel extends Model
{
    protected $table = 'ddxm_setting';

    //获取数据
    public function getContent($type){
        return $this ->where('type',$type)->find();
    }
}