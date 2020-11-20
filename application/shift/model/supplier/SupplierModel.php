<?php
namespace app\shift\model\supplier;

use think\Model;
use think\Cache;
use think\Db;

class SupplierModel extends Model
{	
	protected $table = 'ddxm_supplier';
	protected $connection = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
       'hostname'    => 'localhost',
        // 数据库名
        'database'    => 'ddxm_admin',
        // 数据库用户名
        'username'    => 'root',
        // 数据库密码
        'password'    => 'root',
        // 数据库连接端口
        'hostport'    => '3306',
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => 'ddxm_',
        // 数据集返回类型
        'resultset_type' => '',
    ];
}