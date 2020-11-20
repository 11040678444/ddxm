<?php
// +----------------------------------------------------------------------
// | 新人专享模块
// +----------------------------------------------------------------------
namespace app\mall_admin_market\model\exclusive;

use app\mall_admin_market\model\exclusive\NewExclusive;
use think\Model;
use think\Db;

class NewExclusiveGoods extends Model
{
    protected $table = 'ddxm_st_exclusive_goods';

    /***
     * 新人专享商品列表
     */
    public function itemList( $data )
    {
        if( !empty($data['page']) && !empty($data['limit']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '1,10';
        }
        $where = [];
        if( !empty($data['st_id']) ){
            $where[] = ['a.st_id','eq',$data['st_id']];
        }
        if( !empty($data['hot']) ){
            $where[] = ['a.hot','eq',$data['hot']];
        }
        if( !empty( $data['bar_code'] ) ){
            $where[] = ['b.bar_code','like','%'.$data['bar_code'].'%'];
        }
        if( !empty($data['item_name']) ){
            $where[] = ['b.item_name','like','%'.$data['item_name'].'%'];
        }
        $where[] = ['a.is_delete','eq',0];
        $list = $this->alias('a')
            ->join('st_exclusive b','a.id=b.ng_id')
            ->where($where)
            ->order('b.price asc')
            ->group('b.item_id')
            ->field('a.id,a.item_id,a.hot,a.st_id,a.st_id as st_name,b.item_name,b.item_pic,ng_id')
            ->page($page)
            ->select()->append(['item_info']);
        $count = $this ->alias('a')->join('st_exclusive b','a.id=b.ng_id')->where($where)->group('b.item_id')->count();
        return ['count'=>$count,'data'=>$list];
    }

    /***
     * 新人专享所有商品列表,wxshop调用
     * $stId 分类ID
     */
    public function itemALLList( $stId )
    {
        $where = [];
        $where[] = ['a.st_id','eq',$stId];
        $where[] = ['a.is_delete','eq',0];
        $list = $this->alias('a')
            ->join('st_exclusive b','a.id=b.ng_id')
            ->where($where)
            ->order('b.price asc')
            ->group('b.item_id')
            ->field('a.id,a.item_id,a.hot,a.st_id,a.st_id as st_name,b.item_name,b.item_pic,ng_id')
            ->select()->append(['item_info'])->toArray();
        return $list;
    }

    /***
     * 商品删除
     */
    public function delItems($data)
    {
        if( empty($data['id']) ){
            return_error('请选择商品');
        }
        $info = $this ->where('id',$data['id'])->find();
        if( $info['is_delete'] == 1 ){
            return_error('该商品已删除');
        }
        //开启事务
        Db::startTrans();
        try{
            $this ->where('id',$data['id'])->setField('is_delete',1);
            (new NewExclusive())
                ->where([
                    ['ng_id','eq',$data['id']],
                    ['item_id','eq',$info['item_id']]
                ])->update(['is_delete'=>1,'update_time'=>time()]);
            //提交事务
            Db::commit();
        }catch (\Exception $e){
            //事务回滚
            Db::rollback();
            return_error($e->getMessage());
        }
        return true;
    }

    /***
     * 获取商品
     * @param $val
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getItemInfoAttr($val,$data){
        $where = [];
        $where[] = ['is_delete','eq',0];
        $where[] = ['ng_id','eq',$data['id']];
        $where[] = ['item_id','eq',$data['item_id']];
        $list = (new NewExclusive())
            ->where($where)
            ->field('id,item_id,item_name,item_pic,old_price,bar_code,price,attr_ids,attr_name,ng_id')
            ->select()
            ->toArray();
        return $list;
    }

    public function getStNameAttr($val){
        return Db::name('st_type')->where('id',$val)->value('title');
    }
    public function getItemPicAttr($val){
        return config('QINIU_URL').$val;
    }

}