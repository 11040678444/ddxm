<?php

namespace app\mall_admin_market\validate\coupon;

use think\Validate;

class Coupon extends Validate
{
    protected $rule = [
        'c_name'  =>  'require',
        'c_type'  =>  'require',
        'c_amo_dis' =>  'require',
        'c_use_scene'  =>  'require',
        'c_use_price' =>  'require',
        'c_use_cill'  =>  'require',
        'c_use_time' =>  'require',
        'c_receive_num'  =>  'require',
        'c_provide_num'  =>  'require',
        'c_content' =>  'require',
        'c_is_show' =>  'require',
        'c_start_time' =>  'require'
    ];
    protected $message  =   [
        'c_name.require' => '请输入优惠券名称',
        'c_type.require' => '请选择优惠券类型',
        'c_amo_dis.require' => '请输入优惠券金额(折扣)',
        'c_use_scene.require' => '请选择使用场景',
        'c_use_price.require' => '请选择适用价格',
        'c_use_cill.require' => '请设置使用门槛',
        'c_use_time.require' => '请设置用卷时间',
        'c_receive_num.require' => '请设置每人领卷次数',
        'c_provide_num.require' => '请设置发放数量',
        'c_content.require' => '请输入卷内容说明',
        'c_is_show.require' => '设置是否显示',
        'c_start_time.require' => '请设置优惠券开始领取时间'
    ];
}