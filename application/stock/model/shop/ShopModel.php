<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\stock\model\shop;

use think\Model;

class ShopModel extends Model
{
    protected $table = 'ddxm_shop';

    //门店列表
    public function getList($data){
        if( !empty($data['page']) && !empty($data['limit']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $where = [];
        $where[] = ['status','eq',1];
        $list = $this ->where($where)->field('id,code,name')->page($page)->select();
        $count = $this ->where($where)->count();
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$list]);
    }
}