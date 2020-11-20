<?php
// +----------------------------------------------------------------------
// | 新人专享用户领取记录表模块
// +----------------------------------------------------------------------
namespace app\mall_admin_market\model\exclusive;

use think\Model;
use think\Db;

class StPayLog extends Model
{
    protected $table = 'ddxm_st_pay_log';

    /***
     * member_id:用户id
     * 查询用户是否已购买
     * return 2表示用户未购买,1表示用户已经购买
     */
    public function userPayLog( $data )
    {
        if( empty($data['member_id']) )
        {
            return false;
        }
        $info = $this ->where('member_id',$data['member_id'])->find();
        return empty($info) ? 2 : 1;
    }
}