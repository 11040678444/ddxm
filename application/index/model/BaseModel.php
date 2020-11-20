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
namespace app\index\model;

use think\Model;

class BaseModel extends Model
{
    // protected $connection = [
    //     // 数据库类型
    //     'type'        => 'mysql',
    //     // 服务器地址
    //     //'hostname'    => '120.55.63.230',
    //  //   'hostname'    => 'localhost',
    //     'hostname'    => '120.79.5.57',
    //     // 数据库名
    //     'database'    => 'ddxx',
    //     // 数据库用户名
    //     'username'    => 'root',
    //     // 数据库密码
    //     'password'    => 'ddxm2019',
    //     // 数据库连接端口
    //     'hostport'    => '3306',
    //     // 数据库编码默认采用utf8
    //     'charset'     => 'utf8',
    //     // 数据库表前缀
    //     'prefix'      => 'tf_',
    //     // 数据集返回类型
    //     'resultset_type' => '',
    // ];

    /**
     * @var bool 自动写入创建和更新的时间戳字段
     */
    protected $autoWriteTimestamp = true;


}
