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
namespace app\index\model\item;

use think\Model;
use think\Cache;
use think\Db;

class ItemCategoryModel extends Model
{

    protected $table = 'ddxm_item_category';

    // protected $connect = [
    //     // 数据库类型
    //     'type'        => 'mysql',
    //     // 服务器地址
    //     //'hostname'    => '120.55.63.230',
    //     //'hostname'    => 'localhost',
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


    /*
    获取商品一级分类
     */
    public function type_list(){
        
    }

    /**
        根据分类的id 查询下级分类
    */
    public function type_child( $data ){
        $db = Db::connect($this->connect);
        $type = $db
            ->name('item_category')
            ->field('tf_item_category.id,tf_item_category.pid,tf_item_category.cname,tf_item_category.thumb,tf_item_category.status,tf_item_category.sort')
            ->where('tf_item_category.status!=-1 and tf_item_category.pid='.$data['type'])
            ->page($data['page'])
            ->order("sort desc")
            ->select();
        return $type;

    }


    /**
    获取门店商品信息
    列表数据
    $where 搜索的条件
    */
    public function getOrderListDatas1($where)
    {
        $where[] = ['a.status','=','1'];
        $where[] = ['b.shop_id','=','18'];      //门店目前还未该，应该改成当前登录的门店账号id
        $array = $this->alias('a')
            ->join('tf_shop_item b','a.id=b.item_id','left')
            ->field('a.id,a.type_id,a.type,a.title,a.time,a.item_type,a.status,a.price,a.bar_code,a.pics,a.status,a.md_standard_price,b.stock')
            ->where($where)
            ->order('a.id DESC');

        // dump( $db->name('item')->getLastSql());die;
        return $array;
    }


    /**
        分割商品图片获取第一张
    */
    public function getPicsAttr($val){
        if( empty($val) ){
            return '9a47a20190318164340290';    //设置的一个默认图片
        }else{
            $images = explode(',', $val);
            return $images['0'];
        }
    }

    /**
    搜索门店商品
    */
    public function search_item($data,$shop_id){
        $where = [];
        if( !empty($data['type']) ){        //商品分类
            //分类id
            $where[] = ['a.type','in',$data['type']];
        }

        if( !empty($data['bar_code']) ){        //商品条形码
            $where[] = ['a.bar_code','=',$data['bar_code']];
        }

        if( !empty($data['title']) ){   //商品名称
            $where[] = ['a.title','like','%'.$data['title'].'%'];
        }
        $where[] = ['a.status','=','1'];        //商品状态正常
        $where[] = ['a.item_type','in','2,3'];       //商品类型门店类型
        $where[] = ['b.shop_id','=',$shop_id];      //门店商品


        $array = $this->alias('a')
            ->join('tf_shop_item b','a.id=b.item_id','left')
            ->join('tf_item_category c','a.type=c.id')
            ->field('a.id,a.type_id,a.type,a.title,a.time,a.item_type,a.status,a.price,a.bar_code,a.pics,a.status,a.md_standard_price,b.stock,c.cname')
            ->where($where)
            ->page($data['page'])
            ->order('a.id DESC,b.stock DESC');
        return $array;
    }

    /**
        根据商品id获取商品列表
    */
    public function settlement_item_list($where){
        $where[] = ['a.status','=','1'];        //商品状态正常
        $where[] = ['a.item_type','in','2,3'];       //商品类型门店类型

        // $where['a.status'] = 1;
        // $where['a.item_type'] = array('2,3');
            
        $array = $this->alias('a')
            ->join('tf_shop_item b','a.id=b.item_id')
            ->join('tf_item_category c','a.type=c.id')
            ->field('a.id,a.type_id,a.type,a.title,a.time,a.item_type,a.status,a.price,a.bar_code,a.pics,a.status,a.md_standard_price,b.stock,b.id shop_item_id,c.pid,c.cname')
            ->where($where)->select();
        // dump($this ->getLastSql());die;
        return $array;
    }



    /**
    根据条形码搜索单商品
    */
    public function search_code_item($bar_code){
        $db = Db::connect($this->connect);
        $list = $db->name('item')
                ->field('id,type_id,type,title,time,item_type,status,price,bar_code,status,md_standard_price')
                ->where('bar_code',$bar_code)->find();
        return $list;
    }


}
