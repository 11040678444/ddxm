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

class OrderGoodsModel extends BaseModel
{
    protected $table = 'tf_order_goods';

    protected $createTime = 'addtime';

    public function getAttrPicAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        if (is_numeric(strpos($value, 'http'))) {
        	return $value;
        }
        return config('domino.qiniu_url').'/'.$value;
    }
}
