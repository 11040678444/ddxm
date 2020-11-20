<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\item\ItemModel;
use app\admin\model\item\ItemCategoryModel;
use app\admin\model\item\ItemCateModel;
use app\admin\model\item\ItemUnitModel;
use app\admin\model\item\ItemSpecsModel;
use think\Db;

use Qiniu\Auth as Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Qiniu\Config;

/**
 * 商品模块
 */
class Item extends Adminbase
{
    //上传测试
    public function test()
    {
        if(request()->isPost()){
            $file = request()->file('file');
            // 要上传图片的本地路径
            $filePath = $file->getRealPath();
            $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
 
            // 上传到七牛后保存的文件名
            $key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
            require_once APP_PATH . '/../vendor/qiniu/autoload.php';
            // 需要填写你的 Access Key 和 Secret Key
            $accessKey = "ChbjC0NsNlFawXdmV9GXZtaoU5rfq5ZS9d919Z1n";
            $secretKey = "Fnd1ud7q77V7qlLlW0uqFna24RD2B-AI_2Jrd0IH";
            // 构建鉴权对象
            $auth = new Auth($accessKey, $secretKey);
            // 要上传的空间
            $bucket = "ddxm-item";
            $domain = "picture.ddxm661.com";
            $token = $auth->uploadToken($bucket);

            // 初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();

            // 调用 UploadManager 的 putFile 方法进行文件的上传
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            // dump($ret);die;
            if ($err !== null) {
                return json(["code"=>1,"msg"=>$err,"data"=>""]);die;
            } else {
                //返回图片的完整URL
                return  json(["code"=>0,"msg"=>"上传完成","data"=>$ret]);die;
            }
        }
    }

    //删除图片
    public function delelteImage()
    {
        $delFileName = input("file");
        if( $delFileName ==null){
            echo "参数不正确";die;
        }
        require_once APP_PATH . '/../vendor/qiniu/autoload.php';
        $auth = new Auth("ChbjC0NsNlFawXdmV9GXZtaoU5rfq5ZS9d919Z1n","Fnd1ud7q77V7qlLlW0uqFna24RD2B-AI_2Jrd0IH");
        $config = new \Qiniu\Config();
        $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
        $res = $bucketManager->delete("ddxm-item",$delFileName);
        if (is_null($res)) {
            return json(["code"=>1,"msg"=>'删除成功',"data"=>""]);die;
        }else{
            return json(["code"=>0,"msg"=>'删除失败,网络错误',"data"=>""]);die;
        }
    }

    /**
     * 商品管理模块列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $Item = new ItemModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $data = $this ->request ->param();
            $where = [];
            if( !empty($data['name']) ){
                $name = $data['name'];
                $where[] = ['title','like',"%$name%"];
            }
            if( !empty($data['type_id']) ){
                $where[] = ['type_id','=',$data['type_id']];
            }
            if( !empty($data['type']) ){
                $where[] = ['type','=',$data['type']];
            }
            if( !empty($data['bar_code']) ){
                $where[] = ['bar_code','like','%'.$data['bar_code'].'%'];
            }
            $where[] = ['status','neq',3];
            $where[] = ['item_type','in','2,3'];
            $list = $Item->where($where)->page($page,$limit)->field('*,id as price')->order('id desc')->select();
            $total = $Item->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }

        //1级分类
        $typeId = Db::name('item_category')->where(['pid'=>0,'status'=>1,'type'=>1,'online'=>0])->field('id,cname')->select();
        $this ->assign('typeId',$typeId);

        return $this->fetch();
    }

    //商品添加
    public function item_add(){
        $data = $this ->request ->param();

        $ItemCategory = new ItemCategoryModel();
        $where['pid'] = 0;
        $where['status'] = 1;
        $where['type'] = 1;
        $where['online'] = 0;
        $category = $ItemCategory->where($where)->order('sort asc')->select();
        $this ->assign('category',$category);

        $ItemUnit = new ItemUnitModel();
        $unit = $ItemUnit->where(['status'=>1])->order('sort asc')->select();
        $this ->assign('unit',$unit);

        $ItemSpecs = new ItemSpecsModel();
        $specs = $ItemSpecs->where(['status'=>1])->order('sort asc')->select();
        $this ->assign('specs',$specs);

        $ItemCate = new ItemCateModel();
        $cate = $ItemCate->where(['status'=>1])->order('id asc')->select();
        $this ->assign('cate',$cate);

        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);

        if( !empty($data['id']) ){
            $list = Db::name('item')->where('id',$data['id'])->find();
            $list['pics'] = explode(',', $list['pics']);
            $this ->assign('list',$list);

            $ItemCategory = new ItemCategoryModel();
            $where['pid'] = $list['type_id'];
            $where['status'] = 1;

            $type = $ItemCategory->where($where)->order('sort asc')->field('id,cname')->select();
            $this ->assign('type',$type);

            $itemPrice = Db::name('item_price')->where('item_id',$data['id'])->select();
            $this ->assign('itemPrice',$itemPrice);
        }
        return $this->fetch();   
    }

    //商品添加的操作
    public function item_doPost(){
        $data = $this ->request ->post();
        if( $data['type_id']==0 ){
            return json(['code'=>0,'msg'=>'请选择一级分类']);
        }
        
        $list = Db::name('item_category')->where(['pid'=>$data['type_id'],'status'=>1])->count();
        // dump($list);die;
        if( $list >0 && ($data['type'] == 0 || empty($data['type'])) ){
            return json(['code'=>0,'msg'=>'请选择二级分类']);
        }
        
        //商品库
//        if( count($data['item_type']) == 2 ){
//            $data['item_type'] = 3;
//        }else if(count($data['item_type']) == 1){
//            if( empty($data['item_type']['0']) ){
//                $data['item_type'] = 1;
//            }
//            if( !empty($data['item_type']['0']) ){
//                $data['item_type'] = 2;
//            }
//        }
        $data['item_type'] = 2;

        $validate = new \app\admin\validate\item\Item;
        if (!$validate->check($data)) {
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        $itemPrice = [];    //商品价格表
        if( !empty($data['all_shop']) ){
            //全部门店
            if( empty($data['all_price']) ){
                return json(['code'=>0,'msg'=>'请输入销售价格']);
            }
            if( $data['all_price'] < $data['all_price1'] ){
                return json(['code'=>0,'msg'=>'销售价格不能低于最低销售价格']);
            }

            if( strlen($data['all_price'])>13 || strlen($data['all_price1'])>13 ){
                return json(['code'=>0,'msg'=>'金额位数不合理']);
            }

            if(empty($data['all_price1'])){ //最低销售价
                $minimum_selling_price = 0;
            }else{
                $minimum_selling_price = $data['all_price1'];
            }
            $shop = Db::name('shop')->where('status',1)->field('id,name')->select();
            foreach ($shop as $key => $value) {
                $array = array('shop_id'=>$value['id'],'status'=>1,'selling_price'=>$data['all_price'],'minimum_selling_price'=>$minimum_selling_price);
                array_push($itemPrice, $array); //商品价格数据
            }
            $data['in_allshop'] = 1;

        }else if(!empty($data['is_use'])) {
            $data['in_allshop'] = 0;
            $shopList = $data['is_use'];
            foreach ($shopList as $key => $value) {
                if( $value['shop_price'] == '' && $value['shop_price1'] != '' ){
                    return json(['code'=>0,'msg'=>'当前有填写的门店未填入销售价格']);
                }
                if( $value['shop_price'] < $value['shop_price1'] ){
                    return json(['code'=>0,'msg'=>'销售价格不能低于最低销售价格']);
                }
                if( $value['status']==1 && $value['shop_price']=='' ){
                    return json(['code'=>0,'msg'=>'当前有使用的门店未填入销售价格']);
                }

                if( !empty($value['shop_price']) ){
                    if( empty($value['status']) ){
                        $status = 0;
                    }else{
                        $status = $value['status'];
                    }
                    if( empty($value['shop_price1']) ){
                        $minimum_selling_price = 0;
                    }else{
                        $minimum_selling_price = $value['shop_price1'];
                    }
                    if( strlen($minimum_selling_price)>13 || strlen($value['shop_price'])>13 ){
                        return json(['code'=>0,'msg'=>'金额位数不合理']);
                    }
                    
                    $array = array('shop_id'=>$key,'status'=>$status,'selling_price'=>$value['shop_price'],'minimum_selling_price'=>$minimum_selling_price);
                    
                    array_push($itemPrice, $array); //商品价格数据
                }

            }
        }
        unset($data['all_shop']);
        unset($data['all_price']);
        unset($data['all_price1']);
        unset($data['is_use']);
        if( !empty($data['images']) ){
            $data['pic'] = $data['images']['0'];
            $data['pics'] = implode(',',$data['images']);
            unset($data['images']);
        }
        unset($data['file']);

        $Item = new ItemModel();
        $info = $Item->where('bar_code',$data['bar_code'])->field('id')->find();
        if( empty($data['id']) ){
            // 启动事务
            if( $info && !empty($data['bar_code']) ){
                return json(['code'=>0,'msg'=>'已存在此商品条码']);
            }

            $data['time'] = time();
            $data['update_time'] = time();
            Db::startTrans();
            try {
                $itemId = $Item ->insertGetId($data);
                foreach ($itemPrice as $key => $value) {
                    $itemPrice[$key]['item_id'] = $itemId;
                    $itemPrice[$key]['user_id'] = session('admin_user_auth')['uid'];
                }
                Db::name('item_price')->insertAll($itemPrice);

                //给商品库存表添加默认数据
                foreach ($itemPrice as $key => $value) {
                    if( $value['status'] == 1 ){
                        $sitemWhere = [];
                        $sitemWhere[] = ['shop_id','=',$value['shop_id']];
                        $sitemWhere[] = ['item_id','=',$value['item_id']];
                        $info = [];
                        $info = Db::name('shop_item')->where($sitemWhere)->find();
                        if( !$info ){
                            Db::name('shop_item')->insert(['shop_id'=>$value['shop_id'],'item_id'=>$value['item_id'],'stock'=>0,'stock_ice'=>0]);   //
                        }
                    }  
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code'=>0,'msg'=>'商品名称不重复']);
            }
        }else{
            if( ($info['id'] != $data['id'] && $info ) && !empty($data['bar_code']) ){
                return json(['code'=>0,'msg'=>'已存在此商品条码']);
            }

            $Item ->where('id',$data['id'])->update($data);
            Db::name('item_price') ->where('item_id',$data['id'])->delete();
            foreach ($itemPrice as $key => $value) {
                $itemPrice[$key]['item_id'] = $data['id'];
                $itemPrice[$key]['user_id'] = session('admin_user_auth')['uid'];
            }
            Db::name('item_price')->insertAll($itemPrice);

            //给商品库存表添加默认数据
            foreach ($itemPrice as $key => $value) {
                if( $value['status'] == 1 ){
                    $sitemWhere = [];
                    $sitemWhere[] = ['shop_id','=',$value['shop_id']];
                    $sitemWhere[] = ['item_id','=',$value['item_id']];
                    $info = [];
                    $info = Db::name('shop_item')->where($sitemWhere)->find();
                    if( !$info ){
                        Db::name('shop_item')->insert(['shop_id'=>$value['shop_id'],'item_id'=>$value['item_id'],'stock'=>0,'stock_ice'=>0]);   //
                    }
                }  
            }
        }
        return json(['code'=>1,'msg'=>'操作成功']);
    }

    //商品删除
    public function item_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $Item = new ItemModel();

        Db::startTrans();
        try {
            $Item ->where('id',$data['id'])->update(['status'=>3,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
            Db::name('item_price')->where('item_id',$data['id'])->setField('status',0);   
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>0,'msg'=>$e->getMessage()]);
        }
        $this ->success('删除成功');
    }

    //商品下架
    public function item_xiajia(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $result = Db::name('item')->whereIn('id',rtrim($data['id'],','))->setField('status',0);
        if( $result ){
            $this ->success('下架成功');
        }else{
            $this ->error('下架失败');
        }
    }
    //商品上架
    public function item_shangjia(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $result = Db::name('item')->whereIn('id',rtrim($data['id'],','))->setField('status',1);
        if( $result ){
            $this ->success('上架成功');
        }else{
            $this ->error('上架失败');
        }
    }

    //选择二级分类
    public function category_select(){
        $data = $this ->request ->post();
        if( empty($data['pid']) ){
            return json(['code'=>0,'msg'=>'父id为空']);
        }
        $ItemCategory = new ItemCategoryModel();
        $where['pid'] = $data['pid'];
        $where['status'] = 1;
        $list = $ItemCategory->where($where)->order('sort asc')->field('id,cname')->select();
        return json(['code'=>1,'data'=>$list]);
    }

    //分类列表
    public function category_list(){
        $data = $this ->request ->param('cname');
        if ($this->request->isAjax()) {
            $ItemCategory = new ItemCategoryModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $cname = $this->request->param('cname');
            $pid =  $this->request->param('pid')?$this->request->param('pid'):0;
            $where = [];
            $where[] = ['pid','=',$pid];
            if( !empty($cname) ){
                $where[] = ['cname','like',"%$cname%"];
            }
            $where[] = ['type','eq',1];
            $where[] = ['online','eq',0];
            $list = $ItemCategory->where($where)->page($page,$limit)->select();
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
        $ItemCategory = new ItemCategoryModel();
        if( !empty($data['id']) ){
            $list = $ItemCategory ->where('id',$data['id'])->field('id,sort,cname,pid')->find();
            $this ->assign('list',$list);
        }
        // $pid = 0;
        // $where = [];
        // $where[] = ['pid','=',$pid];
        // $where[] = ['status','=',1];
        // $where[] = ['type','=',1];
        // $pid = $ItemCategory ->where($where)->order('sort asc')->field('id,cname')->select();
        $this ->assign('pid',$data['pid']);
        return $this->fetch();
    }

    //分类添加的操作
    public function category_doPost(){
        $data = $this ->request ->post();
        if( empty($data['cname']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemCategory = new ItemCategoryModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['addtime'] = time();
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
        $ItemCategory = new ItemCategoryModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }

        $where['status'] = 1;
        $where['pid'] = $data['id'];
        $info = $ItemCategory ->where($where)->select();    //查询是否存在二级分类
        // if( count($info) >0 ){
        //     $this ->error('此分类下存在正在使用的二级分类,请勿删除');
        // }
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
        $ItemCategory = new ItemCategoryModel();
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

    //分区列表
    public function cate_list(){
        if ($this->request->isAjax()) {
            $ItemCate = new ItemCateModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','=',1];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemCate->where($where)->page($page,$limit)->order('id asc')->select();
            $total = $ItemCate->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加分区
    public function cate_add(){
        $data = $this ->request ->param();
        $ItemCate = new ItemCateModel();
        if( !empty($data['id']) ){
            $list = $ItemCate ->where('id',$data['id'])->field('id,sort,title')->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加分区的操作
    public function cate_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemCate = new ItemCateModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemCate ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemCate ->where('id',$data['id'])->update($data);
        }

        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //分区删除
    public function cate_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemCate = new ItemCateModel();
        $result = $ItemCate ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('删除成功');
        }else{
            $this ->error('删除失败');
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

    //规格列表
    public function specs_list(){
        if ($this->request->isAjax()) {
            $ItemSpecs = new ItemSpecsModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','=',1];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemSpecs->where($where)->page($page,$limit)->order('sort asc')->select();
            $total = $ItemSpecs->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加规格
    public function specs_add(){
        $data = $this ->request ->param();
        $ItemSpecs = new ItemSpecsModel();
        if( !empty($data['id']) ){
            $list = $ItemSpecs ->where('id',$data['id'])->field('id,sort,title')->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加规格的操作
    public function specs_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemSpecs = new ItemSpecsModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemSpecs ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemSpecs ->where('id',$data['id'])->update($data);
        }

        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //规格删除
    public function specs_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemSpecs = new ItemSpecsModel();
        $result = $ItemSpecs ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('删除成功');
        }else{
            $this ->error('删除失败');
        }
    }


}
