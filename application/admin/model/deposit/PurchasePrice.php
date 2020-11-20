<?php

// +----------------------------------------------------------------------
// | 成本
// +----------------------------------------------------------------------
namespace app\admin\model\deposit;

use think\Model;

class PurchasePrice extends Model
{
    //修改门店的成本
    public function edit($data)
    {
        if ( empty($data['item_id']) )
        {
            return ['code'=>0,'msg'=>'商品ID不能为空'];
        }
        if ( empty($data['md_one_price']) )
        {
            return ['code'=>0,'msg'=>'门店成本单价不能为空'];
        }
        if ( empty($data['shop_id']) )
        {
            return ['code'=>0,'msg'=>'请选择门店'];
        }

        if ( isset($data['shop_ids']) && is_array($data['shop_ids']) && count($data['shop_ids']) )
        {
            array_push($data['shop_ids'],$data['shop_id']);
            $shop_ids = $data['shop_ids'];
        }else{
            $shop_ids = [$data['shop_id']];
        }

        //先查询成本
        $where = [];
        $where[] = ['shop_id','in',implode(',',$shop_ids)];
        $where[] = ['item_id','in',$data['item_id']];
        $where[] = ['stock','>',0];

        $list = $this ->where($where) ->field('id,md_price,store_cose,stock')->select();

        $editData = []; //需要修改的数据
        $logData = [];  //修改日志
        if ( count($list) )
        {
            foreach ( $list as $k=>$v )
            {
                $arr = [];
                $arr = [
                    'id'    =>$v['id'],
                    'md_price'    =>$data['md_one_price'],  //门店成本
                ];
                array_push($editData,$arr);

                $arr = [];
                $arr = [
                    'pp_id' =>$v['id'],
                    'old_md_price' =>$v['md_price'],
                    'new_md_price' =>$data['md_one_price'],
                    'create_id' =>session('admin_user_auth')['uid'],
                    'create_name' =>session('admin_user_auth')['username'],
                ];
                array_push($logData,$arr);
            }
        }
        $this ->startTrans();
        $res = 1;
        if ( count($editData) )
        {
            $res = $this ->allowField(true)->isUpdate(true)->saveAll($editData);
        }
        if ( $res && count($logData) )
        {
            $res = (new PurchasePriceLog()) ->allowField(true)->saveAll($logData);
        }
        !$res ? $this ->rollback() : $this ->commit();
        return $res;
    }
}