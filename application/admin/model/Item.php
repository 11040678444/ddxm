<?php

// +----------------------------------------------------------------------
// | 订单模型
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;

class ItemModel extends Model
{
    protected $table = 'tf_item';

     public $connect = [
    // // 数据库类型
     'type'     => 'mysql',
    // // 服务器地址
     'hostname' => '120.79.5.57',
    // // 数据库名
     'database' => 'ddxx',
    // // 用户名
     'username' => 'root',
    // // 密码
     'password' => 'yu12346',
    // // 端口
     'hostport' => '3306',
    // // 数据库编码默认采用utf8
     'charset'  => 'utf8mb4',
    // // 数据库表前缀
     'prefix'   => 'tf_',
     "authcode" => 'OV4w80Ndr23wt4yW1j',
    // //#COOKIE_PREFIX#
     ];
    /*
    获取商品分类
     */
    public function type_list(){

        $db = Db::connect(config('ddxx'));

        $type = $db
        ->name('item_category')
        ->field('tf_item_category.id,tf_item_category.pid,tf_item_category.cname,tf_item_category.thumb,tf_item_category.status,tf_item_category.sort')
        ->where('tf_item_category.status!=-1')
        ->order("sort desc")
        ->select();
        //print_r($type);die;
        $type_list = $this->categoryTree($type);

        return $type_list;
    }
    /*
    分类递归
     */
    public function categoryTree($obj,$pid=0,$count=''){
        $arr = array();
        foreach ($obj as $key => $value) {
            if($value['pid'] == $pid){
                $data['id']         = $value['id'];
                $data['pid']        = $value['pid'];
                $data['status']     = $value['status'];
                $data['thumb']      = $value['thumb'];
                $data['cname']      = $value['cname'];
                $data['sort']       = $value['sort'];
                $data['count']       = $count;
                $data['children']   = $this->categoryTree($obj,$value['id'],$count.'|--');
                /*if(empty($data['children'])){
                    unset($data['children']);
                }*/
                $arr[] = $data;
                unset($data);
            }
        }
        return $arr;
    }
    /*
    分类删除
     */
    public function type_delete($catid){

        $db = Db::connect(config('ddxx'));

        $data['status'] = '-1';

        $res = $db
        ->name('item_category')
        ->where('status=1')
        ->where('id='.$catid)
        ->update($data);
        //print_r($type);die;
        if($res){
            return 1;
        }else{
            return '删除失败！';
        }
    }
    /*
    获取单条分类
     */
    public function type_find($catid){

        $db = Db::connect(config('ddxx'));

        $typeinfo = $db
        ->name('item_category')
        ->field('id,pid,cname,thumb,status,sort')
        ->where('status!=-1')
        ->where('id='.$catid)
        ->find();
        //print_r($type);die;
        return $typeinfo;
    }
    /*
    编辑分类操作
     */
    public function type_editPost($catid,$data){

        $db = Db::connect(config('ddxx'));

        Db::startTrans();
        try{
            $res = $db
            ->name('item_category')
            ->field('id,pid,cname,thumb,status,sort')
            ->where('id='.$catid)
            ->update($data);

            if($data['status']==1){
                $status['status'] = '1';

                $rec = $db
                ->name('item_category')
                ->field('id,pid,cname,thumb,status,sort')
                ->where('status=-2')
                ->where('pid='.$catid)
                ->update($status);

            }elseif($data['status']==0){
                $status['status'] = '-2';

                $rec = $db
                ->name('item_category')
                ->field('id,pid,cname,thumb,status,sort')
                ->where('status=1')
                ->where('pid='.$catid)
                ->update($status);
            }

            Db::commit();
            return 1;
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 0;
        }
    }
    /*
    分类添加
     */
    public function type_add($data){

        $db = Db::connect(config('ddxx'));

        $res = $db->name('item_category')->insert($data);

        if($res){
            return 1;
        }else{
            return '添加失败！';
        }
    }
    /*
    分区分类拉取
     */
    public function typefind($catid){

        $db = Db::connect(config('ddxx'));

        $data = $db->name('item_category')
        ->field('id,pid,cname,thumb,status,sort')
        ->where('status!=-1')
        ->where("cate_id=".$catid)
        ->select();

        $type_list = $this->categoryTres($data);

        return $type_list;

    }
    /*
    分类递归
     */
    public function categoryTres($obj,$pid=0,$count=''){
        $arr = array();
        foreach ($obj as $key => $value) {
            if($value['pid'] == $pid){
                $data['id']         = $value['id'];
                $data['pid']        = $value['pid'];
                $data['status']     = $value['status'];
                $data['thumb']      = $value['thumb'];
                $data['cname']      = $value['cname'];
                $data['sort']       = $value['sort'];
                $data['count']       = $count;
                $data['children']   = $this->categoryTres($obj,$value['id'],$count.'|--');
                /*if(empty($data['children'])){
                    unset($data['children']);
                }*/
                $arr[] = $data;
                unset($data);
            }
        }
        return $arr;
    }
    /*
    分类区域下拉
    */
    public function tiemYlian($field = '')
    {
        $db = Db::connect(config('ddxx'));
        if ($field) {
            $fields = $field;
        }else{
            $fields = 'id,pid,cname,thumb,status,sort';
        }
        $data = $db->name('item_category')
        ->field($fields)
        ->where('status!=-1')
        //->where("cate_id=".$catid)
        ->where("pid=0")
        ->select();

        return $data;
    }
    /*
    分类区域下拉
    */
    public function itemTlian($catid)
    {
        $db = Db::connect(config('ddxx'));

        $data = $db->name('item_category')
        ->field('id,pid,cname,thumb,status,sort')
        ->where('status!=-1')
        ->where("pid=".$catid)
        ->select();

        return $data;
    }
    /*
    商品列表查询
     */
    public function item_list(){

        $db = Db::connect(config('ddxx'));

        $iteminfo = $db
        ->name('item')
        ->field("tf_item.id,tf_item_attr.id as attr_id,tf_item_attr.attr_name,tf_item_attr.attr_names,tf_item_attr.bar_code,tf_item.type_id,tf_item.title,tf_item_attr.status,FROM_UNIXTIME(tf_item.addtime,'%Y年%m月%d日') as addtime,tf_item.thumb,tf_item_category.cname")
        ->join('tf_item_category',"tf_item.type=tf_item_category.id")
        ->join('tf_item_attr',"tf_item.id=tf_item_attr.item_id")
        ->where("tf_item.status!=-1")
        ->where("tf_item_attr.status!=-1")
        ->order("tf_item.id DESC")
        //->fetchSql(true)
        ->paginate(20);
        //print_r($type);die;
        /*foreach ($iteminfo as $key => $value) {
            $iteminfo[$key]['title'] = $value['title'].$value['attr_name'].$value['attr_names'];
        }*/

        return $iteminfo;
    }

    //列表数据
    public function getOrderListDatas($where)
    {
        $db = Db::connect(config('ddxx'));

        exit;
//        return $db->name('item')
//            ->alias('a')
//            ->join('tf_item_category c',"a.type=c.id",'LEFT')
//            ->join('tf_item_attr b',"a.id=b.item_id")
//            ->field('a.id,a.type_id,a.type,a.title,b.id as attr_id,b.attr_name,b.attr_names,b.status,a.addtime,a.thumb,c.cname,c.pid,b.bar_code,b.jing_code')
//            ->where($where)
//            ->where('a.status','neq',-1)
//            ->where('c.status','=',1)
//            ->order('a.id DESC');
         $db->name('item')
            ->alias('a')
            ->join('tf_item_category c',"a.type=c.id",'LEFT')
            ->field('a.id,a.type_id,a.type,a.title,a.time,c.cname,a.item_type,a.status,a.price,a.bar_code')
            ->where($where)
            ->where('c.status','=',1)
            ->order('a.id DESC');
    }

    public function get_attr_list()
    {
        $db = Db::connect(config('ddxx'));
        return $db->name('attr')->field('attr_name,id')->select();
    }

    /*
    商品列表搜索
     */
    public function item_search($search=null){

        $db = Db::connect(config('ddxx'));

        if($search!=null){
            $where["tf_item.title"] = array('like',"%$search%");
        }else{
            $where = "1=1";
        }

        $iteminfo = $db
        ->name('item')
        ->field("tf_item.id,tf_item_attr.id as attr_id,tf_item_attr.attr_name,tf_item_attr.attr_names,tf_item_attr.bar_code,tf_item_attr.jing_code,tf_item.type_id,tf_item.title,tf_item_attr.status,FROM_UNIXTIME(tf_item.addtime,'%Y年%m月%d日') as addtime,tf_item.thumb,tf_item_category.cname")
        ->join('tf_item_category',"tf_item.type=tf_item_category.id")
        ->join('tf_item_attr',"tf_item.id=tf_item_attr.item_id")
        ->where($where)
        ->order("tf_item.id DESC")
        //->fetchSql(true)
        ->paginate(20);
        //print_r($iteminfo);die;

        return $iteminfo;
    }
    /*
    专区列表
     */
    public function part_list(){

        $db = Db::connect(config('ddxx'));

        $iteminfo = $db
        ->name('item_cate')
        ->field("id,title")
        ->select();
        //print_r($type);die;

        return $iteminfo;
    }

    /**
     * [part_list 获取专区信息]
     * @return [type] [description]
     */
    public function item_cate($cate_id = 0){
        if ($cate_id) {
            $data = Db::connect(config('ddxx'))->name('item_cate')->where(['id'=>$cate_id])->field("id,title,thumb,left_pic,right_pic")->find();
        }else{
            $data = Db::connect(config('ddxx'))->name('item_cate')->field("id,title,thumb,left_pic,right_pic")->select();
            foreach ($data as &$v) {
                $v['thumb'] = config('file_server_url').$v['thumb'];
                $v['left_pic'] = config('file_server_url').$v['left_pic'];
                $v['right_pic'] = config('file_server_url').$v['right_pic'];
            }
        }
        return $data;
    }

    /**
     * [itemCateUpdate 专区信息更新]
     * @param  integer $data [数据]
     * @param  integer $id   [主键]
     * @return [type]        [description]
     */
    public function itemCateUpdate($data = 0,$id = 0)
    {
        if ($id) {
          return Db::connect(config('ddxx'))->name('item_cate')->where(['id'=>$id])->update($data);
        }
        return Db::connect(config('ddxx'))->name('item_cate')->insert($data);
    }




    /*
    商品添加
     */
    public function addpost($data){

        $db = Db::connect(config('ddxx'));

        $iteminfo = $db->name('item')->insert($data);

        $item_id  = $db->name('item')->getLastInsID();
        //print_r($type);die;

        return $item_id;
    }
    /*
    商品属性添加
     */
    public function attradd($data){

        $db = Db::connect(config('ddxx'));

        $res=$db->name('item_attr')->insert($data);
        //print_r($type);die;

        return $res;
    }
    /*
    商品查询
     */
    public function itemfind($item_id){

        $db = Db::connect(config('ddxx'));

        $itemfind=$db->name('item')->where("id=".$item_id)->find();

        return $itemfind;
    }
    /*
    商品修改
     */
    public function editpost($data,$item_id){

        $db = Db::connect(config('ddxx'));

        $attr_list=$db->name('item')->where("id=".$item_id)->update($data);
        //print_r($type);die;

        return $attr_list;
    }
    /*
    商品属性修改
     */
    public function attredit($data,$attr_id){

        $db = Db::connect(config('ddxx'));

        $attr_list=$db->name('item_attr')->where("id=".$attr_id)->update($data);
        //print_r($type);die;

        return $attr_list;
    }
    /*
    上下架删除操作
     */
    public function item_operation($itemid,$status){

        $db = Db::connect(config('ddxx'));

        $data['status'] = $status;

        if($status==1){
            $where['status'] = 0;
        }else if($status==0){
            $where['status'] = 1;
        }else{
            $where='1=1';
        }

        $res = $db
        ->name('item')
        ->where('id='.$itemid)
        ->where($where)
        //->fetchSql(true)
        ->update($data);

        //print_r($res);die;
        if($res){
            return 1;
        }else{
            return '操作失败！';
        }
    }
    /*
    商品属性上下架删除操作
     */
    public function attr_operation($itemid,$status){

        $db = Db::connect(config('ddxx'));

        $data['status'] = $status;

        $res = $db
        ->name('item_attr')
        ->where('id='.$itemid)
        //->fetchSql(true)
        ->update($data);

        //print_r($res);die;
        if($res){
            return 1;
        }else{
            return '操作失败！';
        }
    }
    /*
    获取所有分类
     */
    public function typexuan(){

        $db = Db::connect(config('ddxx'));

        $type = $db
        ->name('item_category')
        ->field('tf_item_category.id as id,tf_item_category.pid as pId,tf_item_category.cname as name')
        ->where('tf_item_category.status=1')
        ->select();

        return $type;
    }
    /*
    获取所有商品
     */
    public function itemxuan(){

        $db = Db::connect(config('ddxx'));

        $iteminfo = $db
        ->name('item')
        ->field("tf_item.id,tf_item.title,tf_item.type as pId")
        ->where("tf_item.status=1")
        ->select();
        //print_r($type);die;

        return $iteminfo;
    }
    /*
    获取所有商品
     */
    public function bar_find($bar_code){

        $db = Db::connect(config('ddxx'));

        $res = $db
        ->name('item_attr')
        ->alias('a')
        ->join('tf_item b',"a.item_id=b.id")
        ->field("a.id")
        ->where("a.bar_code=".$bar_code)
        ->where('a.status','neq',-1)
        ->where('b.status','neq',-1)
        ->find();
        //print_r($type);die;

        return $res;
    }
    /*
    获取所有商品
     */
    public function jing_find($jing_code){

        $db = Db::connect(config('ddxx'));

        $res = $db
        ->name('item_attr')
        ->alias('a')
        ->join('tf_item b',"a.item_id=b.id")
        ->field("a.id")
        ->where("a.jing_code=".$jing_code)
        ->where('a.status','neq',-1)
        ->where('b.status','neq',-1)
        ->find();
        //print_r($type);die;

        return $res;
    }
}
