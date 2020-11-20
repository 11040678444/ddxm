<?php

namespace app\admin\validate\shop;

use think\Validate;

class ShopWorker extends Validate
{
    protected $rule = [
        'name'  =>  'require',
        'sid' =>  'require',
        'post_id'  =>  'require',
        'mobile' =>  'require|mobile',
    ];

    protected $message  =   [
        'name.require' => '请输入员工姓名',
        'sid.require'     => '请选择门店',
        'post_id.require'   => '请选择岗位',
        'mobile.require'  => '请输入手机号',
        'mobile.mobile'  => '请输入正确的手机号',
    ];
}