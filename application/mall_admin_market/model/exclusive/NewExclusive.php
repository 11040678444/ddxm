<?php
// +----------------------------------------------------------------------
// | 新人专享模块
// +----------------------------------------------------------------------
namespace app\mall_admin_market\model\exclusive;

use app\mall_admin_market\model\exclusive\NewExclusiveGoods;
use think\Model;
use think\Db;

class NewExclusive extends Model
{
    protected $table = 'ddxm_st_exclusive';
    /***
     * 添加商品
     */
    public function addItem($data)
    {
        if( empty($data['st_id']) ){
            return_error('请选择分类');
        }
        if( empty( $data['item'] ) ){
            return_error('请选择商品');
        }

        $new_exclusive_data = [];   //所有商品
        $new_goods_data = [];   //所有商品id,包括所含商品
        $item_ids = [];
        $rule = '/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/';     //金额规则
        foreach ( $data['item'] as $k=>$v )
        {
            if( empty($v['item_id']) ){
                return_error('请输入商品id');
            }
            if( empty($v['item_pic']) ){
                return_error('请传入商品图');
            }
            if( empty($v['item_name']) ){
                return_error('请传入商品名称');
            }
            if( !preg_match($rule,$v['old_price']) ){
                return_error('原价金额格式有误');
            }
            if( !preg_match($rule,$v['price']) ){
                return_error('现价金额格式有误');
            }
            $arr = [];
            $arr = [
                'item_id'   =>$v['item_id'],
                'item_pic'   =>$v['item_pic'],
                'bar_code'   =>!empty($v['bar_code'])?$v['bar_code']:'',
                'item_name'   =>$v['item_name'],
                'old_price'   =>$v['old_price'],
                'price'   =>$v['price'],
                'attr_ids'   =>!empty($v['attr_ids'])?$v['attr_ids']:'',
                'attr_name'   =>!empty($v['attr_name'])?$v['attr_name']:'',
                'create_time'   =>time(),
                'virtually_num'   =>!empty($data['virtually_num'])?$data['virtually_num']:0,
            ];
            array_push($new_exclusive_data,$arr);
            if( !in_array($v['item_id'],array_column($new_goods_data,'item_id')) ){
                array_push($new_goods_data,['item_id'=>$v['item_id']]);
            }
            if( !in_array($v['item_id'],$item_ids) ){
                array_push($item_ids,$v['item_id']);
            }
        }
//        $where = [];
//        $where[] = ['item_id','in',implode(',',$item_ids)];
//        $where[] = ['is_delete','eq',0];
//        $info = $this
//            ->where($where)
//            ->column('item_name');
//        if( count($info) > 0 ){
//            return_error(implode(',',$info).'已是新人专享商品啦');
//        }
        foreach ( $new_goods_data as $k=>$v ){
            $new_goods_data[$k]['item'] = [];
            foreach ( $new_exclusive_data as $k1=>$v1 ){
                if( $v['item_id'] == $v1['item_id'] ){
                    array_push($new_goods_data[$k]['item'],$v1);
                }
            }
        }
        //判断不重复
        foreach ( $new_goods_data as $k=>$v ){
            foreach ( $v['item'] as $k1=>$v1 ){
                $map = [];
                $map[] = ['item_id','eq',$v1['item_id']];
                if( !empty($v1['attr_ids']) ){
                    $map[] = ['attr_ids','eq',$v1['attr_ids']];
                }
                $map[] = ['is_delete','eq',0];
                $repeat = $this ->where($map)->field('id')->find();
                if( $repeat ){
                    $msg = $v1['item_name'].'商品重复或商品的规格重复!';
                    return_error($msg);
                }
            }
        }
        //开启事务
        Db::startTrans();
        try{
            foreach ( $new_goods_data as $k=>$v ){
                $ng_id = Db::name('st_exclusive_goods')
                    ->insertGetId(
                        [
                            'item_id'=>$v['item_id'],
                            'hot'=>2,
                            'st_id'=>$data['st_id']
                        ]
                    );
                $item = $v['item'];
                foreach ( $item as $k1=>$v1 ){
                    $item[$k1]['ng_id'] = $ng_id;
                }
                $this ->insertAll($item);
            }
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
     * 商品详情
     * @param $data
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function itemInfo($data)
    {
        if( empty($data['id']) ){
            return_error('ID ERROR');
        }
        $list = $this ->where('ng_id',$data['id'])->where('is_delete',0)
            ->field('id,item_id,item_name,item_pic,old_price,bar_code,price,attr_ids,attr_name,already_num,virtually_num')
            ->select()->toArray();
        return ['data'=>$list];
    }

    /***
     * 编辑商品
     */
    public function editItem($data)
    {
        if( empty($data['st_id']) ){
            return_error('st_id error');
        }
        if( empty($data['ng_id']) ){
            return_error('ng_id error');
        }
        if( empty($data['item']) ){
            return_error('item error');
        }
        $rule = '/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/';     //金额规则
        foreach ( $data['item'] as $k=>$v ){
            $data['item'][$k]['update_time'] = time();
            if( !preg_match($rule,$v['price']) ){
                return_error('现价金额格式有误');
            }
        }
        $exclusive_goods_data = [
            'st_id' =>$data['st_id']
        ];
        //启动事务
        Db::startTrans();
        try{
            (new NewExclusiveGoods()) ->where('id',$data['ng_id']) ->update($exclusive_goods_data);
            $this ->saveAll($data['item']);
            if( !empty($data['deleteIDs']) ){
                $this ->where([
                    ['id','in',implode(',',$data['deleteIDs'])]
                ]) ->update(['is_delete'=>1,'update_time'=>time()]);
            }
            //启动
            Db::commit();
        }catch (\Exception $e){
            //回滚
            Db::rollback();
        }
        return true;
    }

    /***
     * 获取图片地址
     * @param $val
     * @return string
     */
    public function getItemPicAttr($val){
        return config('QINIU_URL').$val;
    }
}