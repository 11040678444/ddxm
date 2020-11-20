<?php

namespace app\admin\model;

use think\Model;

class BaseModel extends Model
{
    protected $connection = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        //'hostname'    => '120.55.63.230',
        'hostname'    => 'localhost',
//        'hostname'    => '120.79.5.57',
        // 数据库名
        'database'    => 'ddxx',
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

    /**
     * @var bool 自动写入创建和更新的时间戳字段
     */
    protected $autoWriteTimestamp = true;


//    public function test()
//    {
//        $request    = request();
//        $module     = $request->module();
//        $controller = $request->controller();
//        $action     = $request->action();
//        $name       = strtolower($module . "/" . $controller . "/" . $action);
//
//        halt($name);
//    }



}
