<?php

// +----------------------------------------------------------------------
// | 专题控制模块
// +----------------------------------------------------------------------
namespace app\mall_admin_market\controller;

use app\common\controller\Backendbase;
use app\mall_admin_market\model\special\SpecialType;
use app\mall_admin_market\model\special\SpecialItem;
class Special extends Backendbase
{
    /***
     * 获取专题类型
     */
    public function getTypeList()
    {
        $data = $this ->request ->param();
        $list = (new SpecialType()) ->getTypeList( $data );
        return_succ($list,'获取成功');
    }

    /***
     * 类型新增与编辑
     */
    public function typeAdd()
    {
        $data = $this ->request ->param();
        $res = (new SpecialType()) ->typeEdit($data);
        if( $res ){
            return_succ([],'操作成功');
        }
        return_error('操作失败');
    }

    /**
     * 类型删除
     */
    public function deleteType()
    {
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return_error('请选择类型');
        }
        $update_data = [];
        $update_data = [
            'status'    =>0,
            'delete_time'    =>time()
        ];
        $res = (new SpecialType()) ->where('id',$data['id'])->update($update_data);
        if( $res ){
            return_succ([],'删除成功');
        }
        return_error('删除失败');
    }

    /***
     * 类型添加商品
     */
    public function add_item()
    {
        $data = $this ->request ->param();
        $res = (new SpecialItem()) ->addOrEdit($data);
        if( $res ){
            return_succ([],'操作成功');
        }
        return_error('操作失败');
    }

    /***
     * 编辑商品
     */
    public function edit_item()
    {
        $data = $this ->request ->param();
        $res = (new SpecialItem()) ->editItem($data);
        if( $res ){
            return_succ([],'操作成功');
        }
        return_error('操作失败');
    }

    /***
     * 获取商品列表
     */
    public function getItemList()
    {
        $data = $this ->request ->param();
        $res = (new SpecialItem()) ->getItemList( $data );
        return_succ($res,'获取成功');
    }

    /***
     * 删除
     */
    public function deleteItem()
    {
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return_error('请选择商品删除');
        }
        $res = (new SpecialItem()) ->where('id',$data['id'])->delete();
        if( $res ){
            return_succ([],'删除成功');
        }
        return_error('删除失败');
    }

    /***
     * 设置热门
     */
    public function setHot(){
        $data = $this ->request ->param();
        $res = (new SpecialItem()) ->setHot($data);
        return_succ([],'操作成功');
    }
}
