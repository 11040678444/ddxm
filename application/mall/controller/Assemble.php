<?php

namespace app\mall\controller;

use app\common\controller\Adminbase;
use app\mall\model\assemble\AssembleModel;
use app\mall\model\items\ItemModel;
use think\Db;
/**
 * 拼团设置
 */
class Assemble extends Adminbase
{
    /***
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function item_list(){
        if ($this->request->isAjax()) {
            $ItemCategory = new AssembleModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            $item_name = $this ->request ->param('name');
            if( $item_name ){
                $where[] = ['a.item_name','like',"%$item_name%"];
            }
            $where[] = ['status','neq',3];
            $list = $ItemCategory
                ->alias('a')
                ->join('assemble_attr b','a.id=b.assemble_id and a.update=b.update')
                ->where($where)
                ->page($page,$limit)
                ->field('a.postage_way,a.id,b.title,a.status,a.item_name,a.old_price,b.price,b.commander_price,a.hot,a.begin_time,a.end_time,a.create_time')
                ->order('a.id desc')->select();

            $total = $ItemCategory
                ->alias('a')
                ->join('assemble_attr b','a.id=b.assemble_id and a.update=b.update')
                ->where($where)
                ->order('a.id desc')->count();
            $total = $ItemCategory->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    /***
     * @return mixed
     */
    public function item_add(){
        return $this ->fetch();
    }

    /***
     * 搜索商品
     */
    public function purchase_item(){
        $data = $this ->request ->param();
        $ItemCategory = new ItemModel();
        $userId = session('admin_user_auth')['uid'];
        $roleid = Db::name('admin')->where('userid',$userId)->value('roleid');
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d', 10);
        $where = [];
        if( $roleid == 5 ){
            $where[] = ['a.user_id','eq',$userId];   //5为新角色（供应商）
        }
        if( isset($data['data']['title']) ){
            $where[] = ['a.title','like','%'.$data['data']['title'].'%'];
        }
        $where[] = ['a.status','neq',3];
//        $where[] = ['b.status','eq',1];
        $where[] = ['item_type','eq',1];
        $where[] = ['unify_specs','eq','1'];

        $list = $ItemCategory
            ->alias('a')
            ->join('specs_goods_price b','a.id=b.gid','LEFT')
            ->where($where)
            ->where('b.status',1)
            ->page($page,$limit)
            ->order('a.sort asc')
            ->field('a.id,a.title,b.price,b.store')
            ->select()->append(['stores']);
        $total = $ItemCategory->alias('a')->where($where)->count();
        $result = array("code" => 0, "count" => $total, "data" => $list);
        return json($result);
    }

    /***
     * 添加拼团活动
     */
    public function add_doPost(){
        $data = $this ->request ->post();
        $data = $data['data'];
        if( $data['store'] != -1 && $data['number']>$data['store'] ){
            return json(['code'=>0,'msg'=>'拼团总数不能大于商品总库存']);
        }
        $assemble = []; //拼团主表数据
        $assemble_update = [];  //副表

        $assemble = array(
            'item_id'   =>$data['item_id'],
            'item_name' =>$data['item_name'],
            'old_price' =>$data['price'],
            'retail' =>$data['retail'],
            'begin_time' =>$data['time']?strtotime($data['time']):0,
            'end_time' =>$data['end_time']?strtotime($data['end_time']):0,
            'hot' =>$data['hot'],
            'status'   =>1,
            'create_time'   =>time(),
            'user_id'   =>session('admin_user_auth')['uid'],
            'update'    =>0,
            'postage_way'    =>!empty($data['postage_way'])?$data['postage_way']:1,
//            'postage_id'   =>$data['postage']
        );
        $assemble_update = array(
            'title'     =>$data['item_name'],
            'item_id'   =>$data['item_id'],
            'item_name'   =>$data['item_name'],
            'price'   =>$data['price1'],
            'commander_price'   =>$data['price2'],
            'people_num'   =>$data['people_num'],
            'buy_num'   =>$data['buy_num'],
            'all_stock'   =>$data['number'],
            'remaining_stock'   =>$data['number'],
            'update'    =>0
        );
        // 启动事务
        Db::startTrans();
        try {
            $assembleId = Db::name('assemble') ->insertGetId($assemble);
            $assemble_update['assemble_id'] = $assembleId;
            Db::name('assemble_attr')->insert($assemble_update);
            if( $data['store'] != -1 ){
               // Db::name('specs_goods_price')->where(['gid'=>$data['item_id']])->setDec('store',$data['number']);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e->getMessage());
            return json(['code'=>0,'msg'=>'添加失败']);
        }
        return json(['code'=>1,'msg'=>'添加成功']);
    }

    /***
     * 编辑拼团
     */
    public function item_save(){
        $data = $this ->request ->param();
        $ItemCategory = new AssembleModel();
        if( empty($data['id']) ){
            $this ->error('缺少id');
        }
        $field = 'a.postage_way,a.id,a.update,c.store,b.title,b.item_id,b.item_name,a.old_price,b.price,b.commander_price,b.people_num,b.buy_num,b.all_stock,b.remaining_stock,a.retail,a.begin_time,a.end_time,a.hot,a.postage_id';
        $list = Db::name('assemble')->alias('a')->where('a.id',$data['id'])
            ->join('assemble_attr b','a.id=b.assemble_id and a.update=b.update')
            ->join('specs_goods_price c','a.item_id=c.gid')
            ->where('c.status','1')
            ->field($field)
            ->find();
        if( $list['store'] == -1 ){
            $list['store1'] = '无限制';
        }else{
            $list['store1'] = $list['store']+$list['all_stock'];
            $list['store'] = $list['store']+$list['all_stock'];
        }

        $this ->assign('list',$list);
        return $this ->fetch();
    }

    public function save_doPost(){
        $data = $this ->request ->post();
        $data = $data['data'];
        if( $data['store'] != -1 && $data['number']>$data['store'] ){
            return json(['code'=>0,'msg'=>'拼团总数不能大于商品总库存']);
        }
        $assemble = []; //拼团主表数据
        $assemble_update = [];  //副表

        $update = $data['update']+1;
        $assemble = array(
            'item_id'   =>$data['item_id'],
            'item_name' =>$data['item_name'],
            'old_price' =>$data['price'],
            'retail' =>$data['retail'],
            'begin_time' =>$data['time']?strtotime($data['time']):0,
            'end_time' =>$data['end_time']?strtotime($data['end_time']):0,
            'hot' =>$data['hot'],
            'status'   =>1,
            'create_time'   =>time(),
            'user_id'   =>session('admin_user_auth')['uid'],
            'update'    =>$update,
            'postage_way'    =>!empty($data['postage_way'])?$data['postage_way']:1,
//            'postage_id'   =>$data['postage']
        );
        $assemble_update = array(
            'title'     =>$data['item_name'],
            'item_id'   =>$data['item_id'],
            'item_name'   =>$data['item_name'],
            'price'   =>$data['price1'],
            'commander_price'   =>$data['price2'],
            'people_num'   =>$data['people_num'],
            'buy_num'   =>$data['buy_num'],
            'all_stock'   =>$data['number'],
            'remaining_stock'   =>$data['number'],
            'update'    =>$update,
            'assemble_id'  =>$data['id'],
        );
        // 启动事务
        Db::startTrans();
        try {
            Db::name('assemble')->where('id',$data['id']) ->update($assemble);
            Db::name('assemble_attr')->insert($assemble_update);
            if( $data['store'] != -1 ){
//                Db::name('specs_goods_price')->where(['gid'=>$data['item_id']])->setInc('store',$data['remaining_stock']);
//                Db::name('specs_goods_price')->where(['gid'=>$data['item_id']])->setDec('store',$data['number']);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>0,'msg'=>'编辑失败']);
        }
        return json(['code'=>1,'msg'=>'编辑成功']);
    }

    /***
     * 下架
     */
    public function assemble_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id为空');
        }
        $res = Db::name('assemble')->where('id',$data['id'])->setField('status',2);
        if( $res ){
            $this ->success('操作成功');
        }else{
            $this ->error('操作失败');
        }
    }

    /***
     * 上架
     */
    public function assemble_start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id为空');
        }
        $res = Db::name('assemble')->where('id',$data['id'])->setField('status',1);
        if( $res ){
            $this ->success('操作成功');
        }else{
            $this ->error('操作失败');
        }
    }
}