<?php

// +----------------------------------------------------------------------
// | 新人专享控制模块
// +----------------------------------------------------------------------
namespace app\mall_admin_market\controller;

use app\mall_admin_market\model\exclusive\NewExclusive;
use app\mall_admin_market\model\exclusive\NewExclusiveGoods;
use app\common\controller\Backendbase;
class Exclusive extends Backendbase
{
    /***
     * 添加新人专享商品
     */
    public function addItem()
    {
        $data = $this ->request ->param();
        $res = (new NewExclusive()) ->addItem($data);
        if( $res ){
            return_succ($res,'添加成功');
        }
        return_error('添加失败');
    }

    /***
     * 新人专享商品列表
     */
    public function itemList()
    {
        $data = $this ->request ->param();
        $list = (new NewExclusiveGoods()) ->itemList($data);
        return_succ($list,'获取成功');
    }

    /***
     * 新人专享商品删除
     */
    public function delItems()
    {
        $data = $this ->request ->param();
        $res = (new NewExclusiveGoods()) ->delItems($data);
        if( $res ){
            return_succ($res,'操作成功');
        }
        return_error('操作失败');
    }

    /***
     * 设置热门
     */
    public function setHot()
    {
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return_error('请选择商品');
        }
        $info = (new NewExclusiveGoods()) ->where('id',$data['id'])->find();
        if(!$info){
            return_error('ERROR');
        }
        $hot = $info['hot']==1?2:1;
        $res = (new NewExclusiveGoods()) ->where('id',$data['id'])->setField('hot',$hot);
        if( $res ){
            return_succ($res,'操作成功');
        }
        return_error('操作失败');
    }

    /**
     *商品详情
     */
    public function itemInfo()
    {
        $data = $this ->request ->param();
        $res = (new NewExclusive()) ->itemInfo($data);
        if( $res ){
            return_succ($res,'操作成功');
        }
        return_error('操作失败');
    }

    /***
     * 编辑商品
     */
    public function editItem()
    {
        $data = $this ->request ->param();
        $res = (new NewExclusive()) ->editItem($data);
        if( $res ){
            return_succ($res,'操作成功');
        }
        return_error('操作失败');
    }

}
