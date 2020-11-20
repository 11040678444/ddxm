<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\items\CategoryModel;
use app\admin\model\items\ItemUnitModel;
use app\admin\model\items\AttributeModel;
use app\admin\model\items\TypeModel;
use app\admin\model\items\CompanyModel;
use app\admin\model\items\SpecsModel;
use app\admin\model\items\PostageModel;
use app\mall\model\items\ItemModel;
use think\Db;


/**
 * 商品模块
 */
class Items extends Adminbase
{
    //分类列表
    public function category_list(){
        $data = $this ->request ->param('cname');
        if ($this->request->isAjax()) {
            $ItemCategory = new CategoryModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $cname = $this->request->param('cname');
            $pid =  $this->request->param('pid')?$this->request->param('pid'):0;
            $where = [];
            $where[] = ['pid','=',$pid];
            if( !empty($cname) ){
                $where[] = ['title','like',"%$cname%"];
            }
            $where[] = ['status','neq',0];
            $list = $ItemCategory->where($where)->page($page,$limit)->order('sort desc')->select();
            $total = $ItemCategory->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list,"pid"=>$pid);
            return json($result);
        }
        $this ->assign('cname',$data);
        return $this->fetch();
    }

    //分类添加
    public function category_add(){
        $data = $this ->request ->param();
        $ItemCategory = new CategoryModel();
        if( !empty($data['id']) ){
            $list = $ItemCategory ->where('id',$data['id'])->field('id,sort,title,pid,thumb')->find();
            $data['pid'] = $list['pid'];
            $this ->assign('list',$list);
        }
        $this ->assign('pid',$data['pid']);
        return $this->fetch();
    }

    //分类添加的操作
    public function category_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        if( $data['pid'] != 0 ){
            if( empty($data['thumb']) ){
                return json(['code'=>-1,'msg'=>'请上传分类展示图']);
            }
            unset($data['file']);
        }
        $ItemCategory = new CategoryModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemCategory ->insert($data);
        }else{
            $info = Db::name('item_category')->where('id',$data['id'])->find();
            if( $info['pid'] == 0 ){
                $t = Db::name('item_category')->where(['pid'=>$data['id']])->count();
                if( $t>0 && $data['pid'] != 0 ){
                    return json(['code'=>0,'msg'=>'此分类下存在二级分类，禁止更改层级']);
                }
            }
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemCategory ->where('id',$data['id'])->update($data);
        }

        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //分类删除
    public function category_del(){
        $data = $this ->request ->param();
        $ItemCategory = new CategoryModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $where['status'] = 1;
        $where['pid'] = $data['id'];
        $info = $ItemCategory ->where($where)->select();    //查询是否存在二级分类
        $result = $ItemCategory ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //分类启用
    public function category_start(){
        $data = $this ->request ->param();
        $ItemCategory = new CategoryModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $result = $ItemCategory ->where('id',$data['id'])->update(['status'=>1]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    //单位列表
    public function unit_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new ItemUnitModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','=',1];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('sort asc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加单位
    public function unit_add(){
        $data = $this ->request ->param();
        $ItemUnit = new ItemUnitModel();
        if( !empty($data['id']) ){
            $list = $ItemUnit ->where('id',$data['id'])->field('id,sort,title')->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加单位的操作
    public function unit_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemUnit = new ItemUnitModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->where('id',$data['id'])->update($data);
        }

        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //单位删除
    public function unit_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new ItemUnitModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('删除成功');
        }else{
            $this ->error('删除失败');
        }
    }

    //属性
    public function attribute_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new AttributeModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $pid = $this ->request ->param('pid')?$this ->request ->param('pid'):0;
            $where = [];
            $where[] = ['pid','eq',$pid];
            $where[] = ['status','neq',0];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('create_time desc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0,'pid'=>$pid, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //选择分类
    public function category_select(){
        $pid = $this ->request->param('pid');
        if( !$pid ){
            return json(['code'=>0,'msg'=>'父id为空']);
        }
        $cate = Db::name('item_categorys')->where('pid',$pid)->field('id,title')->select();
        if( count($cate)<=0 ){
            return json(['code'=>0,'msg'=>'此分类下无二级分类，请重新选择']);
        }
        return json(['code'=>1,'msg'=>'获取成功','data'=>$cate]);
    }

    //添加属性
    public function attribute_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('item_attribute') ->where('id',$data['id'])->find();
            $this ->assign('list',$list);
            $data['pid'] = $list['pid'];
        }

        $category = Db::name('item_categorys')->where(['pid'=>0,'status'=>1])->select();
        $this ->assign('pid',$data['pid']);
        $this ->assign('category',$category);
        return $this->fetch();
    }

    //添加属性的操作
    public function attribute_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title'])){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        if( mb_strlen($data['title'])>4 ){
            return json(['code'=>-1,'msg'=>'名称不能超过4个字']);
        }
        if( $data['pid'] == 0 && empty($data['category_id']) ){
            return json(['code'=>-1,'msg'=>'请选择分类']);
        }else{
            $data['category_id'] = Db::name('item_attribute')->where('id',$data['pid'])->value('category_id');
        }
        $ItemUnit = new AttributeModel();
        $count = $ItemUnit ->where(['category_id'=>$data['category_id']])->count();

        if( empty($data['id']) ){
            if( $count >=4 ){
                return json(['code'=>-1,'msg'=>'该分类下已有4个属性，请重新选择分类']);
            }
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->where('id',$data['id'])->update($data);
        }
        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //属性禁用
    public function attribute_del(){
        $data = $this ->request ->param();
        $ItemCategory = new AttributeModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $where['status'] = 1;
        $where['pid'] = $data['id'];
        $info = $ItemCategory ->where($where)->select();    //查询是否存在二级分类
        $result = $ItemCategory ->where('id',$data['id'])->update(['status'=>2,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //属性启用
    public function attribute_start(){
        $data = $this ->request ->param();
        $ItemCategory = new AttributeModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $result = $ItemCategory ->where('id',$data['id'])->update(['status'=>1,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    //类型列表
    public function type_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new TypeModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','=',1];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('sort asc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加类型
    public function type_add(){
        $data = $this ->request ->param();
        $ItemUnit = new TypeModel();
        if( !empty($data['id']) ){
            $list = $ItemUnit ->where('id',$data['id'])->field('id,title')->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加类型的操作
    public function type_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemUnit = new TypeModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->where('id',$data['id'])->update($data);
        }

        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //类型删除
    public function type_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new TypeModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('删除成功');
        }else{
            $this ->error('删除失败');
        }
    }

    //快递公司列表
    public function company_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new CompanyModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','neq',0];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('sort asc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加类型
    public function company_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('item_company') ->where('id',$data['id'])->field('id,title,sort')->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加类型的操作
    public function company_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemUnit = new CompanyModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->where('id',$data['id'])->update($data);
        }

        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //类型删除
    public function company_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new CompanyModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>2,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('删除成功');
        }else{
            $this ->error('删除失败');
        }
    }

    //类型启用
    public function company_start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new CompanyModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>1,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    //规格
    public function specs(){
        if ($this->request->isAjax()) {
            $ItemUnit = new SpecsModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['pid','eq',0];
            $where[] = ['status','neq',0];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('sort asc')->select()->append(['children']);
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加规格
    public function specs_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('item_specs') ->where('id',$data['id'])->find();
            $info = DB::name('item_specs')->where('pid',$data['id'])->column('title');
            $res =  '';
            foreach ($info as $k=>$v){
                $res = $res."\n".$v;
            }
            $list['children'] = $res;
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //g规格添加
    public function specs_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) || empty($data['titles']) ){
            return json(['code'=>-1,'msg'=>'规格名或规格值不能为空']);
        }
        $list = explode("\n",$data['titles']);
        $tt = [];
        foreach ($list as $k=>$v){
            $v = preg_replace("/ /","",$v);
            $v = preg_replace("/&nbsp;/","",$v);
            $v = preg_replace("/　/","",$v);
            $v = preg_replace("/\r\n/","",$v);
            $v = str_replace(chr(13),"",$v);
            $v = str_replace(chr(10),"",$v);
            $v = str_replace(chr(9),"",$v);
            if( $v !='' ){
                array_push($tt,$v);
            }
        }
        $info = [];     //二级
        foreach ( $tt as $k=>$v ){
            if( !in_array($v,$info) &&  $v !='' ){
                array_push($info,$v);
            }
        }
        $infoDatas = [];
        foreach ($info as $k=>$v){
            $arr = array('title'=>$v);
            array_push($infoDatas,$arr);
        }
        unset($data['titles']);
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            // 启动事务
            Db::startTrans();
            try {
                $pid = Db::name('item_specs')->insertGetId($data);
                foreach ($infoDatas as $k=>$v){
                    $infoDatas[$k]['pid'] = $pid;
                    $infoDatas[$k]['user_id'] = session('admin_user_auth')['uid'];
                    $infoDatas[$k]['create_time'] = time();
                    $infoDatas[$k]['update_time'] = time();
                    $infoDatas[$k]['update_id'] = session('admin_user_auth')['uid'];
                }
                Db::name('item_specs')->insertAll($infoDatas);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code'=>2,'msg'=>'服务器出错,请及时联系管理员']);
            }
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            Db::name('item_specs')->where('pid',$data['id'])->delete();
            foreach ($infoDatas as $k=>$v){
                $infoDatas[$k]['pid'] = $data['id'];
                $infoDatas[$k]['user_id'] = session('admin_user_auth')['uid'];
                $infoDatas[$k]['create_time'] = time();
                $infoDatas[$k]['update_time'] = time();
                $infoDatas[$k]['update_id'] = session('admin_user_auth')['uid'];
            }
            Db::name('item_specs')->insertAll($infoDatas);
        }
        return json(['code'=>1,'msg'=>'操作成功']);
    }

    //规格启用
    public function specs_start(){
        $id = $this ->request->param('id');
        if( !$id ){
            $this ->error('缺少参数');
        }
        $where[] = ['id|pid','eq',$id];
        $result = Db::name('item_specs') ->where($where)->update(['status'=>1,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    //规格禁用
    public function specs_del(){
        $id = $this ->request->param('id');
        if( !$id ){
            $this ->error('缺少参数');
        }
        $where[] = ['id|pid','eq',$id];
        $result = Db::name('item_specs') ->where($where)->update(['status'=>2,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //运费
    public function postage_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new PostageModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','neq',0];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('id desc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加运费模板
    public function postage_add(){
        $data = $this ->request ->param();
        $Post = new PostageModel();
        if( !empty($data['id']) ){
            $list = $Post ->where('id',$data['id'])->find();
            $list['area_ids'] = explode(',',$list['area_ids']);
            $this ->assign('list',$list);
        }
        $area = Db::name('area')->where(['pid'=>0,'grade'=>1])->order('sort asc')->field('id,area_name,areacode')->select();
        $this ->assign('area',$area);
        return $this->fetch();
    }

    //运费添加操作
    public function postage_doPost(){
        $data = $this ->request->post();
        $Postage = new PostageModel();
        if( empty($data['area_ids']) ){
            return json(['code'=>2,'msg'=>'请选择城市']);
        }
        if( empty($data['title']) || empty($data['type']) ){
            return json(['code'=>2,'msg'=>'输入标题或选择运费类型']);
        }
        $data['area_ids'] = implode(',',$data['area_ids']);
        $areaWhere[] = ['id','in',$data['area_ids']];
        $area = DB::name('area')->where($areaWhere)->column('area_name');
        $data['area_names'] = implode(',',$area);
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $res = $Postage ->insert($data);
        }else{
            $res = $Postage ->update($data);
        }
        if( $res ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //运费删除
    public function postage_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new PostageModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>2]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //运费启用
    public function postage_start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new PostageModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>1]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }


}
