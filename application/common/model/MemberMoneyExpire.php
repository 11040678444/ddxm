<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/16 0016
 * Time: 上午 9:46
 *
 * 限时余额
 */

namespace app\common\model;
use think\Model;

class MemberMoneyExpire extends Model
{
    public function setMoneyExpire($datas)
    {
        $this->saveAll($datas);
        die("23");

    }
}