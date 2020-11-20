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

class ServiceModel extends BaseModel
{

    protected $table = 'tf_service';

    protected $connect = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        //'hostname'    => '120.55.63.230',
        //'hostname'    => 'localhost',
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

    //查询会员商品列表
    public function service_list($data){
        $where['status'] = 1;
        $db = Db::connect($this->connect);
        return $this->where($where)
                    ->field('s_id,sname,id,status,addtime,bar_code,standard_price,is_online,cover,icon,remark')
                    ->page($data['page'])
                    ->order('s_id desc');
    }

    //根据商品id查询
    public function get_service_list($where){

        $where['status'] = 1;
        $result = $this->where($where)
                    ->field('s_id,sname,id,status,addtime,bar_code,standard_price,is_online,cover,icon,remark')
                    ->order('s_id desc');
        // dump($this ->getLastSql());
        // dump($result);die;
        return $result;
    }

}
