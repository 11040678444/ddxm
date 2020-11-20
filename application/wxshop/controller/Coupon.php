<?php

// +----------------------------------------------------------------------
// | 优惠券控制器
// +----------------------------------------------------------------------
namespace app\wxshop\controller;
use app\wxshop\model\coupon\CouponModel;

class Coupon extends Base
{
    /***
     * 优惠券列表
     */
    public function getItemCoupon(){
        $data = $this ->request->param();
        $list = (new CouponModel()) ->getCouponList($data);
        return_succ($list,'获取成功');
    }

    /***
     *领取优惠券
     */
    public function collectCoupon(){
        $data = $this ->request ->param();
        $memberId = self::getToken();
        $data['member_id'] = $memberId;
        $list = (new CouponModel()) ->collectCoupon($data);
        if( $list ){
            return_succ([],'领取成功');
        }
    }

    /***
     * 我的优惠券
     */
    public function getMemberCoupon( $postData = array(),$kill = 0 ){
        $data = !empty($postData)?$postData:$this->request->param();
        $memberId = self::getToken();
        $data['member_id'] = $memberId;
        $data['kill'] = $kill;
        $list = (new CouponModel()) ->memberCoupon($data);
        if( empty($kill) ){
            return_succ($list,'获取成功');
        }else{
            return $list;
        }
    }

    /***
     * 优惠券详情
     */
    public function getCouponInfo(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return_error('请选择优惠券');
        }
        $info = (new CouponModel()) ->getCouponInfo($data);
        return_succ($info,'查询成功');
    }
}