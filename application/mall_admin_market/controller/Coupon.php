<?php

// +----------------------------------------------------------------------
// | 优惠券控制模块
// +----------------------------------------------------------------------
namespace app\mall_admin_market\controller;

use app\common\controller\Backendbase;
use app\mall_admin_market\model\coupon\CouponModel;
class Coupon extends Backendbase
{
    /***
     * 优惠券列表
     */
    public function get_list(){
        $data = $this ->request ->param();
        $list = (new CouponModel()) ->getList($data);
        return $list;
    }

    /***
     * 添加或编辑优惠券
     */
    public function coupon_doPost(){
        $data = $this ->request ->param();
        $res = (new CouponModel()) ->addOrEdit($data);
        return $res;
    }

    /***
     * 优惠券详情
     */
    public function coupon_info(){
        $data = $this ->request ->param();
        $res = (new CouponModel()) ->info($data);
        return $res;
    }

    /***
     * 优惠券领取明细
     */
    public function CouponReceiveInfo(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return_error('请选择优惠券');
        }
        $res = (new CouponModel()) ->couponReceiveInfo($data);
        return_succ($res,'获取成功');
    }

    /**
     * 删除优惠券
     */
    public function coupon_delete(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择优惠券']);
        }
        $res = (new CouponModel()) ->where('id',$data['id']) ->setField('is_delete',1);
        if( $res ){
            return json(['code'=>200,'msg'=>'删除成功']);
        }
        return json(['code'=>100,'msg'=>'删除失败']);
    }

    /***
     * 用户未使用的优惠券禁用
     */
    public function update_status(){
        $data = $this ->request ->param();
        $data['nickname'] = self::getUserInfo()['nickname'];
        $res = (new CouponModel()) ->updateIsUse($data);
        return_succ($res,'操作成功');
    }
}
