<?php

namespace app\mall\controller;

use app\common\controller\Adminbase;
use think\Db;
/**
 * 供应商
 */
class Supplier extends Adminbase
{
    //供应商列表
    public function supplier_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = Db::name('shop_supplier');
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','neq',0];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加供应商
    public function supplier_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('shop_supplier') ->where('id',$data['id'])->field('id,title')->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加供应商的操作
    public function supplier_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemUnit = Db::name('shop_supplier');
        if( empty($data['id']) ){
            $data['create_time'] = time();
            $data['update_time'] = time();
            $result = $ItemUnit ->insert($data);
        }else{
            $data['update_time'] = time();
            $result = $ItemUnit ->where('id',$data['id'])->update($data);
        }
        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //供应商删除
    public function supplier_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = Db::name('shop_supplier');
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time()]);
        if( $result ){
            $this ->success('删除成功');
        }else{
            $this ->error('删除失败');
        }
    }
}