<?php

namespace app\admin\validate\item;

use think\Validate;

class Item extends Validate
{
    protected $rule = [
        'type_id'  =>  'require',
        // 'type'  =>  'require',
        'title' =>  'require',
        'unit_id'  =>  'require',
        'specs_id' =>  'require',
        // 'bar_code'  =>  'require',
        // 'stock_alert' =>  'require',
        'item_type'  =>  'require',
        'cate_id'  =>  'require',
        // 'content' =>  'require'
    ];

    protected $message  =   [
        'type_id.require' => '请选择一级分类',
        // 'type.require' => '请选择二级分类',
        'title.require'     => '请输入商品名',
        'unit_id.require'   => '请选择单位',
        'specs_id.require'  => '请选择规格',
        // 'bar_code.require'  => '请输入条形码',
        // 'stock_alert.require'   => '请输入库存预警值',
        'item_type.require'  => '请选择商品库',
        'cate_id.require'  => '请选择分区',
        // 'content.require'  => '请输入详情',
    ];
}