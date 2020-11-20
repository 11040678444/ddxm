<?php

// +----------------------------------------------------------------------
// | 订单模型
// +----------------------------------------------------------------------
namespace app\admin\model;
use \think\Db;
use \think\Model;

class ItemModel extends BaseModel
{
    protected $table = 'tf_item';
     /*private*/
    /*
    获取商品分类
     */
   public function getOrderListDatas($where)
    {
        $db = Db::connect(config("ddxx"));
        return $db->name('item')
            ->alias('a')
            ->join('tf_item_category c',"a.type=c.id",'LEFT')
            ->field('a.id,a.type_id,a.type,a.title,a.time,c.cname,a.item_type,a.status,a.price,a.bar_code')
            ->where($where)
            ->where('a.status','neq',-1)
            ->where('c.status','=',1)
            ->order('a.id DESC');
    }
    public function dbmysql($dbName){
        $db = Db::connect([
        // // 数据库类型
         'type'     => 'mysql',
        // // 服务器地址
         'hostname' => '127.0.0.1',//'120.79.5.57'
        // // 数据库名
         'database' => 'ddxx',
        // // 用户名
         'username' => 'root',
        // // 密码
         'password' => 'root',//ddxm2019
        // // 端口
         'hostport' => '3306',
        // // 数据库编码默认采用utf8
         'charset'  => 'utf8mb4',
        // // 数据库表前缀
         'prefix'   => 'tf_',
         "authcode" => 'OV4w80Ndr23wt4yW1j',
        // //#COOKIE_PREFIX#
        ]);
        return $db->name($dbName);
    }
}
