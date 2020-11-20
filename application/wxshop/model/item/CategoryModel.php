<?php
/*
    股东数据统计表
*/
namespace app\wxshop\model\item;
use think\Model;
use think\Db;

/***
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class CategoryModel extends Model
{
    protected $table = 'ddxm_item_category';

    public function getThumbAttr($val){
        if( $val == '' ){
            return '';
        }
        return "http://picture.ddxm661.com/".$val;
    }

    public function child(){
         /*
            * 参数一:关联的模型名
            * 参数二:关联的模型的id
            * 参数三:当前模型的关联字段
         * */
        return $this->hasMany('CategoryModel','pid','id')->field('id,cname,thumb,cate_id,thumb,pid')->order('id asc')->where(['status'=>1]);
    }

    /**
     * 获取选择分类下的所有分类
     */
    public function getAllCategory($pid=0){
        $where = [];
        $where[] = ['online','eq',1];
        $where[] = ['status','eq',1];
        $where[] = ['pid','eq',$pid];
        $where[] = ['type','eq',1];
        $list = $this ->where($where) ->field('id,cname,thumb,cate_id,thumb,pid') ->order('sort asc, id desc') ->select();
        if( count($list) <=0 ){
            return [];
        }
        foreach ( $list as $k=>$v ){
            $child = self::getAllCategory($v['id']);
            $list[$k]['child'] = $child;
        }
        return $list;
    }

    /**
     * 获取选择分类下的所有分类
     */
    public function getAllCategoryList($id=0){
        $mysql_edition = \think\facade\App::version();
        $where = [];
        $where[] = ['online','eq',1];
        $where[] = ['status','eq',1];
        $where[] = ['id','eq',$id];
        $where[] = ['type','eq',1];
        $list = $this ->where($where) ->field('id,cname,thumb,cate_id,thumb,pid') ->order('sort asc, id desc') ->find();
        $categoryLists = self::getAllCategory($id);
        if( count($categoryLists) > 0 ){
            $categoryIds = [];  //最终的分类id
            foreach ( $categoryLists as $k1=>$v1 ){
                //第一层
                if( count($v1['child']) > 0 ){
                    $oneChild = $v1['child'];
                    foreach ( $oneChild as $k2=>$v2 ){
                        array_push($categoryIds,$v2['id']);
                    }
                }else{
                    //表示第一层已经是最底层了
                    array_push($categoryIds,$v1['id']);
                }
            }
            //查询分类下的商品id
            $classWhere = [];
            $classWhere[] = ['category_id','in',implode(',',$categoryIds)];
            $itemIds = Db::name('item_class')->where($classWhere)->group('item_id') ->column('item_id');    //去重查询商品id
        }else{
            $itemIds = Db::name('item_class')->where('category_id',$id)->group('item_id') ->column('item_id');
        }
        return $itemIds;
    }


    /***
     * 获取制定分类的商品
     */
    public function getCategory($id){

    }
}