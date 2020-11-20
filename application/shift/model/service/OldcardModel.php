<?php
namespace app\shift\model\service;

use think\Model;
use think\Cache;
use think\Db;

class OldcardModel
{
    //设置公共的数据库连接
    protected $connection = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        //'hostname'    => '120.55.63.230',
        'hostname'    => 'localhost',
        // 'hostname'    => '120.79.5.57',
        // 数据库名
        'database'    => 'test',
        // 数据库用户名
        'username'    => 'root',
        // 数据库密码
        'password'    => 'root',
        // 数据库连接端口
        'hostport'    => '3306',
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => 'tf_',
        // 数据集返回类型
        'resultset_type' => '',
    ];

    public function old_card(){
        $db = Db::connect($this->connection);
        $data = $db ->name('service_card')->select();
        return $data;
    }

}