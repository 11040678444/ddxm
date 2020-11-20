<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\model\order;

use app\admin\model\BaseModel;

class OrderExpressModel extends BaseModel
{
    protected $table = 'tf_order_express';


    public function getOrderExpress($order_id)
    {
        $data = $this->where(['order_id'=>$order_id])
            ->field('express_name,express_code,express_sn')
            ->find();
        if (empty($data)) {
        	return [];
        }else{
        	return $data->toArray();
        }
    }
}
