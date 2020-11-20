<?php
// +----------------------------------------------------------------------
// | 品牌模型
// +----------------------------------------------------------------------
namespace app\common\model\brand;

use think\Model;
use think\Db;
class BrandModel extends Model
{
    protected $table = 'ddxm_brand';

    public function getList($data){
        if( !empty($data['page']) && !empty($data['limit']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $where = [];
        if( !empty($data['title']) ){
            $where[] = ['title','like','%'.$data['title'].'%'];
        }
        if( isset($data['is_hot']) && $data['is_hot'] !='' ){
            $where[] = ['is_hot','eq',$data['is_hot']];
        }
        if( isset($data['type']) && $data['type'] !='' ){
            $where[] = ['type','eq',$data['type']];
        }
        $where[] = ['status','eq',1];
        $list = $this ->where($where)->field('id,title,pinyin,simplicity,tag') ->page($page)->order('sort asc')->select();
        $count = $this ->where($where) ->count();
        return json(['code'=>200,'count'=>$count,'data'=>$list]);
    }
}