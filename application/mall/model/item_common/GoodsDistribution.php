<?php
// +----------------------------------------------------------------------
// | erp 商品表模型
// +----------------------------------------------------------------------
namespace app\mall\model\item_common;

use think\Db;
use think\Model;
class GoodsDistribution extends Model {
    protected $connection = [
        // 数据库类型
        'type'     => 'mysql',
        // 服务器地址
        'hostname' => '120.79.5.57',
//        'hostname' => '127.0.0.1',
        // 数据库名
        'database' => 'ddxm_erp',
        // 用户名
        'username' => 'ddxm_erp', //
//        'username' => 'root', //
        // 密码
        'password' => 'WZS5Mi4SHt8WxrPd',
//        'password' => 'root',
        // 端口
         'hostport' => '3339',
//        'hostport' => '3306',
        // 数据库编码默认采用utf8
        'charset'  => 'utf8mb4',
        // 数据库表前缀
        'prefix'   => 'ddxm_',
    ];
    protected $table = 'ddxm_goods_distribution';
}
