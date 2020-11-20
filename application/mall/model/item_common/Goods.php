<?php
// +----------------------------------------------------------------------
// | erp 商品表模型
// +----------------------------------------------------------------------
namespace app\mall\model\item_common;

use think\Db;
use think\Model;
class Goods extends Model {
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
    protected $table = 'ddxm_goods';
    //临时的添加erp商品
    public function addGoods($data,$goods_id)
    {
        $this ->startTrans();
        $data['erp_item']['id'] = $goods_id;
        $res = $this ->allowField(true)->isUpdate(false)->save($data['erp_item']);
        $id = $goods_id;
        if ( $res )
        {
            foreach ( $data['specs_goods'] as $k=>$v )
            {
                $data['specs_goods'][$k]['gid'] = $id;
            }
            $res = ( new GoodsSpecsPrice() ) ->allowField(true)->isUpdate(false)->saveAll($data['specs_goods']);
        }

        if ( $res )
        {
            foreach ( $data['itemClassErp'] as $k=>$v )
            {
                $data['itemClassErp'][$k]['goods_id'] = $id;
            }
            $res = ( new GoodsClass() ) ->allowField(true)->isUpdate(false)->saveAll($data['itemClassErp']);
        }
        if ( $res )
        {
            $data['goodsDistribution']['goods_id'] = $id;
            $res = ( new GoodsDistribution() ) ->allowField(true)->isUpdate(false)->save($data['goodsDistribution']);
        }

        if ( $res )
        {
            foreach ( $data['resourceErp'] as $k=>$v )
            {
                $data['resourceErp'][$k]['goods_id'] = $id;
            }
            $res = ( new GoodsResource() ) ->allowField(true)->isUpdate(false)->saveAll($data['resourceErp']);
        }

        //!$res ? $this ->rollback() : $this ->commit();
        return $res;
    }

    //临时的修改erp商品
    public function saveGoods($data,$goods_id)
    {
        $id = $goods_id;
        $this ->startTrans();
        $res = $this ->allowField(true)->isUpdate(true)->save($data['erp_item'],['id'=>$id]);

        if ( $res )
        {
            $res = ( new GoodsSpecsPrice() ) ->destroy(['gid'=>$id]);
            if ( $res )
            {
                $res = ( new GoodsSpecsPrice() ) ->allowField(true)->isUpdate(false)->saveAll($data['specs_goods']);
            }
        }

        if ( $res )
        {
            $res = ( new GoodsClass() ) ->destroy(['goods_id'=>$id]);
            if ( $res )
            {
                $res = ( new GoodsClass() ) ->allowField(true)->isUpdate(false)->saveAll($data['itemClassErp']);
            }

        }
        if ( $res )
        {
            $res = ( new GoodsDistribution() ) ->allowField(true)->isUpdate(true)->save($data['goodsDistribution'],['goods_id'=>$id]);
        }

        if ( $res )
        {
            $res = ( new GoodsResource() ) ->destroy(['goods_id'=>$id]);
            if ( $res )
            {
                $res = ( new GoodsResource() ) ->allowField(true)->isUpdate(false)->saveAll($data['resourceErp']);
            }
        }
        //!$res ? $this ->rollback() : $this ->commit();
        return $res;
    }
}
