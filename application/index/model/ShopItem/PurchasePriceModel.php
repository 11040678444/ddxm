<?php
/*
	订单控制器
*/
namespace app\index\model\ShopItem;

use think\Model;
use think\Cache;
use think\Db;

class PurchasePriceModel extends Model
{
	protected $table = 'tf_purchase_price';

	protected $connection = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        //'hostname'    => '120.55.63.230',
     //   'hostname'    => 'localhost',
        'hostname'    => '120.79.5.57',
        // 数据库名
        'database'    => 'ddxx',
        // 数据库用户名
        'username'    => 'root',
        // 数据库密码
        'password'    => 'ddxm2019',
        // 数据库连接端口
        'hostport'    => '3306',
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => 'tf_',
        // 数据集返回类型
        'resultset_type' => '',
    ];
}