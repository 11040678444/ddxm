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
use think\Cache;
use think\Db;
use app\index\model\ServiceModel;

class WorkerModel extends BaseModel
{

    protected $table = 'tf_worker';

    protected $connect = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        //'hostname'    => '120.55.63.230',
//        'hostname'    => 'localhost',
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

    /**
    查询服务人员
    **/
    public function getWorker($where){
        return $this->where($where)->order('id desc');
    }

    //服务类型
    public function getTypeAttr($val){
        $server_name = (new ServiceModel()) ->where(['id'=>['in',$val]])->field('sname')->select();

        return $this ->arrayFieldTransferString($server_name,'sname');
    }
    function arrayFieldTransferString($array,$field = '',$cut = ',')
    {
        $str = '';
        for ($i = 0; $i < count($array); $i++) {
            if ($i == count($array) -1) {
                $str .= $array[$i][$field];
            }else{
                $str .= $array[$i][$field].$cut;
            }
        }
        return $str;
    }

}
