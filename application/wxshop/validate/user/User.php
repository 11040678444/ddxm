<?php

namespace app\wxshop\validate\user;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'name'  =>  'require',
        'phone' =>  'require|mobile',
        'area_ids'  =>  'require',
        'area_names' =>  'require',
        'address' =>  'require',
        'default' =>  'require'
    ];

    protected $message  =   [
        'name.require' => '请输入收货人姓名',
        'phone.require'     => '请输入手机号码',
        'phone.mobile'   => '请输入正确的手机号码',
        'area_ids.require'  => '请选择地址',
        'area_names.require'  => '请选择地址',
        'address.require'  => '请输入详细地址',
        'default.require'  => '请选择是否设置为默认',
    ];
}