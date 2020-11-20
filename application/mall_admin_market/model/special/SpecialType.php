<?php

// +----------------------------------------------------------------------
// | 专题模块
// +----------------------------------------------------------------------
namespace app\mall_admin_market\model\special;

use think\Model;
use think\Db;

/**
 * 专题--
 * Class CouponModel
 * @package app\mall_admin_market\model\coupon
 */
class SpecialType extends Model
{
    protected $table = 'ddxm_st_type';
    //获取类型
    public function getTypeList( $data )
    {
        if( !empty($data['page']) && !empty($data['limit']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $where = [];
        if( !empty($data['type']) ){
            $where[] = ['type','eq',$data['type']];
        }else{
            $where[] = ['type','eq',1];
        }
        $where[] = ['status','eq',1];
        $list = $this ->field('id,title')->where($where)->page($page)->order('sort asc')->select()->toArray();
        $count = $this ->where($where)->count();
        return ['data'=>$list,'count'=>$count];
    }

    //类型新增与编辑
    public function typeEdit($data)
    {
        if( empty($data['title']) ){
            return_error('请输入类型标题');
        }
        if( empty($data['id']) ){
            $data['create_time'] = time();
            $res = $this ->insert($data);
        }else{
            $data['update_time'] = time();
            $res = $this ->where('id',$data['id'])->update($data);
        }
        return $res;
    }
}